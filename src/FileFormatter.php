<?php


namespace DragoonBoots\YamlFormatter;

use DragoonBoots\YamlFormatter\Yaml\YamlDumper;
use DragoonBoots\YamlFormatter\Yaml\YamlDumperOptions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Format YAML file(s)
 */
class FileFormatter
{

    /**
     * @var YamlDumper
     */
    private $dumper;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * FileFormatter constructor.
     *
     * @param YamlDumperOptions|null $options
     * @param YamlDumper|null $yamlDumper
     * @param Parser|null $yamlParser
     */
    public function __construct(
        ?YamlDumperOptions $options = null,
        ?YamlDumper $yamlDumper = null,
        ?Parser $yamlParser = null
    ) {
        $this->dumper = $yamlDumper ?? new YamlDumper($options);
        $this->parser = $yamlParser ?? new Parser();
    }

    /**
     * Format file(s)
     *
     * @param string $inputPath
     *  Source path.  If this is a directory, recursively format all YAML files.
     * @param string $outputPath
     *  Destination path.  This must be writable.
     * @param callable|null $progressCallback
     *  A callback with the signature `(int $current, int $total, string $filename)` for progress reporting.
     */
    public function format(string $inputPath, string $outputPath, ?callable $progressCallback = null): void
    {
        if (is_file($inputPath)) {
            $pathinfo = pathinfo($inputPath);
            // Fake a Finder with a single file
            $finder = [
                new SplFileInfo(
                    $inputPath,
                    $pathinfo['dirname'],
                    $pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['basename']
                ),
            ];
            $singleOutput = true;
        } elseif (is_dir($inputPath)) {
            $finder = new Finder();
            $finder->files()->in($inputPath)
                ->name(['*.yaml', '*.yml']);
            $singleOutput = false;
        } else {
            throw new \RuntimeException('The input path is not a file or directory.');
        }

        $count = 0;
        $total = count($finder);
        foreach ($finder as $fileInfo) {
            $source = $this->parser->parseFile($fileInfo->getPathname());
            // Ensure file ends with line feed
            $formatted = trim($this->dumper->dump($source))."\n";
            if ($singleOutput) {
                $dest = $outputPath;
            } else {
                $dest = $outputPath.DIRECTORY_SEPARATOR.$fileInfo->getRelativePathname();
            }
            if (file_put_contents($dest, $formatted) === false) {
                throw new \RuntimeException('Could not write to output path '.$dest);
            }

            // Progress
            ++$count;
            if ($progressCallback !== null) {
                call_user_func($progressCallback, $count, $total, $fileInfo->getRelativePathname());
            }
        }
    }
}
