<?php

namespace DragoonBoots\YamlFormatter\tests;

use DragoonBoots\YamlFormatter\FileFormatter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use PHPUnit\Framework\TestCase;

/**
 * Test FileFormatter
 *
 * @covers \DragoonBoots\YamlFormatter\FileFormatter
 * @uses   \DragoonBoots\YamlFormatter\Yaml\YamlDumper
 * @uses   \DragoonBoots\YamlFormatter\Yaml\YamlDumperOptions
 * @uses   \DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilder
 * @uses   \DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilderOptions
 * @uses   \Symfony\Component\Yaml\Parser
 * @uses   \Symfony\Component\Yaml\Dumper
 */
class FileFormatterTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $fileRoot;

    public function setUp(): void
    {
        $structure = [
            'file1.yaml' => <<<YAML
            scalar_field: value
            list:
              - item1
              - item2
            referenced_list:
              - item1
              - item2
            referenced_scalar_field: value
            mapping_field:
              inner_field: 'inner value 1'
            other_mapping_field:
              inner_field: 'inner value 1'
            deep_mapping_field:
              inner_field: 'inner value 1'
            deep_mapping_field_extra:
              inner_field: 'inner value 1'
              other_field: 'other value 1'
            YAML,
            'file2.yml' => <<<YAML
            scalar_field: value
            list:
              - item3
              - item4
            referenced_list:
              - item3
              - item4
            referenced_scalar_field: value
            mapping_field:
              inner_field: 'inner value 2'
            other_mapping_field:
              inner_field: 'inner value 2'
            deep_mapping_field:
              inner_field: 'inner value 2'
            deep_mapping_field_extra:
              inner_field: 'inner value 2'
              other_field: 'other value 2'
            YAML,
        ];
        $this->fileRoot = vfsStream::setup('root', null, $structure);
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat(string $inputPath, string $outputPath, int $fileCount, array $fileContents)
    {
        $fileFormatter = new FileFormatter();
        $last = 0;
        $progress = function (int $current, int $total, string $filename) use ($fileCount, $fileContents, &$last) {
            $this->assertGreaterThan($last, $current);
            $this->assertEquals($fileCount, $total);
            $last = $current;
        };
        $fileFormatter->format($inputPath, $outputPath, $progress);

        // Test file contents
        $newStructure = vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure();
        $this->assertEquals($fileContents, $newStructure['root']);
    }

    public function formatDataProvider()
    {
        return [
            'single file' => [
                'inputPath' => 'vfs://root/file1.yaml',
                'outputPath' => 'vfs://root/file1.yaml',
                'fileCount' => 1,
                'fileContents' => [
                    'file1.yaml' => <<<YAML
                        scalar_field: value
                        list: &list
                          - item1
                          - item2
                        referenced_list: *list
                        referenced_scalar_field: value
                        mapping_field: &mapping_field
                          inner_field: &mapping_field.inner_field 'inner value 1'
                        other_mapping_field: *mapping_field
                        deep_mapping_field: *mapping_field
                        deep_mapping_field_extra:
                          inner_field: *mapping_field.inner_field
                          other_field: 'other value 1'
                        
                        YAML,
                    'file2.yml' => <<<YAML
                        scalar_field: value
                        list:
                          - item3
                          - item4
                        referenced_list:
                          - item3
                          - item4
                        referenced_scalar_field: value
                        mapping_field:
                          inner_field: 'inner value 2'
                        other_mapping_field:
                          inner_field: 'inner value 2'
                        deep_mapping_field:
                          inner_field: 'inner value 2'
                        deep_mapping_field_extra:
                          inner_field: 'inner value 2'
                          other_field: 'other value 2'
                        YAML,
                ],
            ],
            'all files' => [
                'inputPath' => 'vfs://root',
                'outputPath' => 'vfs://root',
                'fileCount' => 2,
                'fileContents' => [
                    'file1.yaml' => <<<YAML
                        scalar_field: value
                        list: &list
                          - item1
                          - item2
                        referenced_list: *list
                        referenced_scalar_field: value
                        mapping_field: &mapping_field
                          inner_field: &mapping_field.inner_field 'inner value 1'
                        other_mapping_field: *mapping_field
                        deep_mapping_field: *mapping_field
                        deep_mapping_field_extra:
                          inner_field: *mapping_field.inner_field
                          other_field: 'other value 1'
                        
                        YAML,
                    'file2.yml' => <<<YAML
                        scalar_field: value
                        list: &list
                          - item3
                          - item4
                        referenced_list: *list
                        referenced_scalar_field: value
                        mapping_field: &mapping_field
                          inner_field: &mapping_field.inner_field 'inner value 2'
                        other_mapping_field: *mapping_field
                        deep_mapping_field: *mapping_field
                        deep_mapping_field_extra:
                          inner_field: *mapping_field.inner_field
                          other_field: 'other value 2'
                        
                        YAML,
                ],
            ],
        ];
    }
}
