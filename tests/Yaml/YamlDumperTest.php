<?php

namespace DragoonBoots\YamlFormatter\tests\Yaml;

use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilder;
use DragoonBoots\YamlFormatter\Yaml\YamlDumper;
use PHPUnit\Framework\TestCase;

/**
 * Test YamlDumper
 *
 * @covers \DragoonBoots\YamlFormatter\Yaml\YamlDumper
 * @uses   \DragoonBoots\YamlFormatter\Yaml\YamlDumperOptions
 * @uses   \DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilderOptions
 * @uses   \Symfony\Component\Yaml\Dumper
 */
class YamlDumperTest extends TestCase
{

    public function testDump()
    {
        $source = [
            'scalar_field' => 'value',
            'list' => [
                'item1',
                'item2',
            ],
            'referenced_list' => [
                'item1',
                'item2',
            ],
            'referenced_scalar_field' => 'value',
            'mapping_field' => [
                'inner_field' => 'inner value',
            ],
            'other_mapping_field' => [
                'inner_field' => 'inner value',
            ],
            'deep_mapping_field' => [
                'inner_field' => 'inner value',
            ],
            'deep_mapping_field_extra' => [
                'inner_field' => 'inner value',
                'other_field' => 'other value',
            ],
        ];
        $expected = <<<YAML
            scalar_field: value
            list: &list
              - item1
              - item2
            referenced_list: *list
            referenced_scalar_field: value
            mapping_field: &mapping_field
              inner_field: &mapping_field.inner_field 'inner value'
            other_mapping_field: *mapping_field
            deep_mapping_field: *mapping_field
            deep_mapping_field_extra:
              inner_field: *mapping_field.inner_field
              other_field: 'other value'
            
            YAML;

        $anchorBuilder = $this->createMock(AnchorBuilder::class);
        $anchorBuilder->expects($this->once())->method('buildAnchors')->with($source)->willReturn(
            [
                'list' => ['item1', 'item2'],
                'mapping_field' => ['inner_field' => 'inner value'],
                'mapping_field.inner_field' => 'inner value',
            ]
        );

        $yamlDumper = new YamlDumper(null, null, $anchorBuilder);
        $actual = $yamlDumper->dump($source);
        $this->assertEquals(trim($expected), trim($actual));
    }
}
