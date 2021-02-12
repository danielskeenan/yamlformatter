<?php


namespace DragoonBoots\YamlFormatter\Yaml;


use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilder;
use Ds\Map;
use Ds\Set;
use Ds\Vector;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;

/**
 * Dump YAML files
 */
class YamlDumper
{
    /**
     * @var Dumper
     */
    private $yamlDumper;

    /**
     * @var AnchorBuilder
     */
    private $anchorBuilder;

    /**
     * @var YamlDumperOptions
     */
    private $options;

    /**
     * @var string
     */
    private $salt;

    /**
     * YamlDumper constructor.
     *
     * @param YamlDumperOptions|null $options
     * @param Dumper|null $yamlDumper
     * @param AnchorBuilder|null $anchorBuilder
     */
    public function __construct(
        ?YamlDumperOptions $options = null,
        ?Dumper $yamlDumper = null,
        ?AnchorBuilder $anchorBuilder = null
    ) {
        $this->options = $options ?? new YamlDumperOptions();
        $this->yamlDumper = $yamlDumper ?? new Dumper($this->options->getIndentation());
        $this->anchorBuilder = $anchorBuilder ?? new AnchorBuilder($this->options->getAnchors());
    }

    /**
     * @param YamlDumperOptions $options
     *
     * @return YamlDumper
     */
    public function setOptions(YamlDumperOptions $options): YamlDumper
    {
        $this->options = $options;
        if ($options->getAnchors() !== null) {
            $this->anchorBuilder->setOptions($options->getAnchors());
        }

        return $this;
    }

    /**
     * Dump data
     *
     * @param iterable $data
     *
     * @return string
     */
    public function dump(iterable $data): string
    {
        $placeholderMap = new Map();
        $replacements = new Map();
        $this->salt = 'REF__'.mt_rand().'__';
        if ($this->options->getAnchors() !== null) {
            // Because anchors and aliases aren't exposed in the serializer, we have to get creative.
            // This algorithm find repeated values, replaces them with a placeholder, then replaces
            // that placeholder the the final value + anchor or an alias as appropriate.
            $anchors = $this->anchorBuilder->buildAnchors($data);
            $this->addPlaceholders($data, $anchors, $placeholderMap, $replacements);
        }
        // Using PHP_INT_MAX means the dumper will never try to inline maps or lists.
        $yaml = $this->yamlDumper->dump($data, PHP_INT_MAX, 0, $this->serializerFlags());
        $yaml = $this->mungeYaml($yaml, $placeholderMap, $replacements);

        return $yaml;
    }

    /**
     * Build the flags for the Symfony YAML dumper
     *
     * @return int
     */
    private function serializerFlags(): int
    {
        $flags = 0;
        if ($this->options->isMultiLineLiteral()) {
            $flags |= YAML::DUMP_MULTI_LINE_LITERAL_BLOCK;
        }
        if ($this->options->isNullAsTilde()) {
            $flags |= YAML::DUMP_NULL_AS_TILDE;
        }

        return $flags;
    }

    /**
     * Scan data for entries that will be converted to anchors and replace them with placeholders.
     *
     * @param iterable $data
     *  Data set
     * @param array $anchors
     *  Map of anchors to their values
     * @param Map $placeholderMap
     *  Map placeholders to anchors
     * @param Map $replacements
     *  Map anchors to their replacement value.  This may contain other references!
     * @param Vector|null $path
     *  Path in dataset, for recursion
     */
    private function addPlaceholders(
        iterable &$data,
        array $anchors,
        Map &$placeholderMap,
        Map &$replacements,
        ?Vector $path = null
    ): void {
        $path = $path ?? new Vector();

        foreach ($data as $key => &$value) {
            $valuePath = clone $path;
            $valuePath->push($key);
            $anchorCheckValue = $value;

            // Recurse if necessary
            if (is_iterable($value) && !is_string($value)) {
                $this->addPlaceholders($value, $anchors, $placeholderMap, $replacements, $valuePath);
            }

            if (($anchor = $this->useAnchorForValue($anchorCheckValue, $valuePath, $anchors)) !== null) {
                // Use refs
                $placeholder = $this->salt.$anchor;
                $placeholderMap->put($placeholder, $anchor);

                // The value has had inner refs added to it, so final replacements need to be stored separately.
                $replacements[$anchor] = $value;
                $value = $placeholder;
            }
        }
    }

    /**
     * Decide which anchor, if any, to use in the given context
     *
     * @param $value
     * @param Vector $valuePath
     * @param array $anchors
     *
     * @return string|null
     *  The anchor name, or null for no anchor
     */
    private function useAnchorForValue($value, Vector $valuePath, array $anchors): ?string
    {
        foreach ($anchors as $anchor => $anchorValue) {
            $anchorPath = new Vector(explode('.', $anchor));
            if ($anchorValue !== $value) {
                // Not a match
                continue;
            }
            // Use only anchors from the same depth
            if (count($valuePath) !== count($anchorPath)) {
                continue;
            }
            // Try to only use anchors where it contextually makes sense
            if (is_iterable($value) && !is_string($value)) {
                // Is a list or map, more likely to be data in a similar context
                return $anchor;
            } elseif ($valuePath->last() === $anchorPath->last()) {
                // Same final keys
                return $anchor;
            }
        }

        return null;
    }

    /**
     * Handle replacements in YAML, adding anchors and aliases as appropriate
     *
     * @param string $yaml
     * @param Map $placeholderMap
     *  Map placeholders to anchors
     * @param Map $replacements
     *  Map anchors to their replacement value.  This may contain other references!
     *
     * @return string
     */
    private function mungeYaml(string $yaml, Map $placeholderMap, Map $replacements): string
    {
        $lines = new Vector(explode("\n", $yaml));
        $usedAnchors = new Set();
        foreach ($lines as $ix => $line) {
            if (preg_match('`^(?P<space>\s*).+(?P<placeholder>'.$this->salt.'.+)$`', $line, $matches) === 0) {
                // No ref used here
                continue;
            }
            $placeholder = $matches['placeholder'];
            $ref = $placeholderMap[$placeholder];
            if (!$usedAnchors->contains($ref)) {
                // First instance of ref, add anchor
                $value = $replacements[$ref];
                $indentLevel = substr_count(
                    $matches['space'],
                    str_repeat(' ', $this->options->getIndentation())
                );
                if (is_string($value)) {
                    // YAML serializer will not create a multiline literal unless it's in a container.
                    // This requires some workarounds.
                    $replacement = $this->yamlDumper->dump([$value], PHP_INT_MAX, 0, $this->serializerFlags());
                    // Remove list syntax from start of string, since it was just a workaround.
                    $replacement = substr(trim($replacement), 2);

                    // Multiline literals need to be indented properly.
                    $isMultiline = substr_count($replacement, "\n") > 0;
                    if ($isMultiline) {
                        $literalLines = [];
                        foreach (explode("\n", $replacement) as $literalLine) {
                            $literalLines[] = rtrim(
                                str_repeat(' ', $this->options->getIndentation() * ($indentLevel + 1))
                                .trim($literalLine)
                            );
                        }
                        // Remove the indentation from the first line
                        $literalLines[0] = ltrim($literalLines[0]);
                        $replacement = implode("\n", $literalLines);
                    }

                    // This controls how whitespace is added between the anchor and value.  Because single and multi-line
                    // strings still have a value of some sort on the same line, the value is not necessarily on the next line.
                    $valueOnNextLine = false;
                } else {
                    $replacement = rtrim($this->yamlDumper->dump($value, PHP_INT_MAX, 0, $this->serializerFlags()));
                    $valueOnNextLine = (is_iterable($value) && !is_string($value));
                    if ($valueOnNextLine) {
                        // A map or list
                        // Insert value on new line
                        $replacement = "\n".$this->indentLines($replacement, $indentLevel + 1);
                    }
                }
                // Add anchor tag
                $replacement = '&'.$ref.($valueOnNextLine ? '' : ' ').$replacement;
                $usedAnchors->add($ref);
            } else {
                // Ref previously defined, add alias
                $replacement = '*'.$ref;
            }
            $newLines = explode("\n", str_replace($placeholder, $replacement, $line));
            $lines->remove($ix);
            $lines->insert($ix, ...$newLines);
        }
        $lines = $lines->map('rtrim');

        return $lines->join("\n");
    }

    /**
     * Indent the lines in $value by $count levels
     *
     * @param string $value
     * @param int $count
     *
     * @return string
     */
    private function indentLines(string $value, int $count = 1): string
    {
        $lines = explode("\n", $value);
        $space = str_repeat(' ', $this->options->getIndentation() * $count);
        foreach ($lines as &$line) {
            $line = $space.$line;
        }

        return implode("\n", $lines);
    }
}
