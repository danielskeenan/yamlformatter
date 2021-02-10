<?php

namespace DragoonBoots\YamlFormatter\tests\AnchorBuilder;

use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilder;
use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilderOptions;
use PHPUnit\Framework\TestCase;

/**
 * Test AnchorBuilder
 *
 * @covers \DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilder
 */
class AnchorBuilderTest extends TestCase
{
    /**
     * @dataProvider buildAnchorsDataProvider
     */
    public function testBuildAnchors(array $source, AnchorBuilderOptions $options, array $expected)
    {
        $anchorBuilder = new AnchorBuilder($options);
        self::assertEquals($expected, $anchorBuilder->buildAnchors($source));
    }

    public function buildAnchorsDataProvider()
    {
        $source = [
            'group' => 'new_group',
            'identifier' => 'new_file',
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

        return [
            'build all refs' => [
                'source' => $source,
                'options' => new AnchorBuilderOptions(),
                'expected' => [
                    'list' => ['item1', 'item2'],
                    'mapping_field' => ['inner_field' => 'inner value'],
                    'mapping_field.inner_field' => 'inner value',
                ],
            ],
        ];
    }
}
