<?php


namespace DragoonBoots\YamlFormatter;

use DragoonBoots\YamlFormatter\Yaml\YamlDumper;
use DragoonBoots\YamlFormatter\Yaml\YamlDumperOptions;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Format YAML file(s)
 */
class FileFormatter
{
    /**
     * @var YamlDumperOptions
     */
    private $options;

    /**
     * @var YamlDumper
     */
    private $dumper;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Schema
     */
    private $schema;

    private const FORMATTER_OPTIONS_FILE = '.yamlformatter.json';

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
        ?Parser $yamlParser = null,
        ?Validator $schemaValidator = null
    ) {
        $this->options = $options ?? new YamlDumperOptions();
        $this->dumper = $yamlDumper ?? new YamlDumper($this->options);
        $this->parser = $yamlParser ?? new Parser();
        $this->validator = $schemaValidator ?? new Validator();

        $schemaPath = implode(
            DIRECTORY_SEPARATOR,
            [
                dirname(__FILE__, 2),
                'resources',
                'yamlformatter.json',
            ]
        );
        $this->schema = Schema::fromJsonString(file_get_contents($schemaPath));
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
        $this->dumper->setOptions($this->useFormatterOptions($inputPath));

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

    /**
     * Choose the formatter options to use
     *
     * @param string $path
     *
     * @return YamlDumperOptions
     */
    private function useFormatterOptions(string $path): YamlDumperOptions
    {
        if (is_file($path)) {
            $path = dirname($path);
        }
        $formatterOptionsPath = implode(DIRECTORY_SEPARATOR, [$path, self::FORMATTER_OPTIONS_FILE]);
        if (is_file($formatterOptionsPath)) {
            // Found an options file
            // Verify schema
            $json = file_get_contents($formatterOptionsPath);
            if ($json === false) {
                throw new \RuntimeException('Cannot read formatter options file at '.$formatterOptionsPath);
            }
            $formatterOptions = json_decode($json);
            if ($formatterOptions === null) {
                throw new \RuntimeException('Formatter options file at '.$formatterOptionsPath.' is invalid JSON');
            }
            $valid = $this->validator->schemaValidation($formatterOptions, $this->schema);
            if ($valid->hasErrors()) {
                throw new \RuntimeException(
                    'Formatter options file at '.$formatterOptionsPath.' is contains invalid options'
                );
            }
            $options = clone $this->options;
            $options->merge(json_decode($json, true));

            return $options;
        }

        return $this->options;
    }
}
