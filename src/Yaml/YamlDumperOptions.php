<?php


namespace DragoonBoots\YamlFormatter\Yaml;

use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilderOptions;

/**
 * Options for YAML dumper
 */
final class YamlDumperOptions
{
    /**
     * Spaces to use on nested nodes.
     *
     * @var int
     */
    private $indentation = 2;

    /**
     * Write PHP objects as YAML maps.
     *
     * @var bool
     */
    private $objectAsMap = true;

    /**
     * Write string literals with multiple lines as a multi-line literal.
     *
     * @var bool
     */
    private $multiLineLiteral = true;

    /**
     * Write null values with a tilde.
     *
     * @var bool
     */
    private $nullAsTilde = true;

    /**
     * Options for the anchor builder, or null for no anchor generation.
     *
     * Defaults to building all anchors
     *
     * @var AnchorBuilderOptions|null
     */
    private $anchors;

    /**
     * YamlDumperOptions constructor.
     */
    public function __construct()
    {
        $this->anchors = new AnchorBuilderOptions();
    }

    /**
     * @return int
     */
    public function getIndentation(): int
    {
        return $this->indentation;
    }

    /**
     * @param int $indentation
     *
     * @return YamlDumperOptions
     */
    public function setIndentation(int $indentation): YamlDumperOptions
    {
        $this->indentation = $indentation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isObjectAsMap(): bool
    {
        return $this->objectAsMap;
    }

    /**
     * @param bool $objectAsMap
     *
     * @return YamlDumperOptions
     */
    public function setObjectAsMap(bool $objectAsMap): YamlDumperOptions
    {
        $this->objectAsMap = $objectAsMap;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiLineLiteral(): bool
    {
        return $this->multiLineLiteral;
    }

    /**
     * @param bool $multiLineLiteral
     *
     * @return YamlDumperOptions
     */
    public function setMultiLineLiteral(bool $multiLineLiteral): YamlDumperOptions
    {
        $this->multiLineLiteral = $multiLineLiteral;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNullAsTilde(): bool
    {
        return $this->nullAsTilde;
    }

    /**
     * @param bool $nullAsTilde
     *
     * @return YamlDumperOptions
     */
    public function setNullAsTilde(bool $nullAsTilde): YamlDumperOptions
    {
        $this->nullAsTilde = $nullAsTilde;

        return $this;
    }

    /**
     * @return AnchorBuilderOptions|null
     */
    public function getAnchors(): ?AnchorBuilderOptions
    {
        return $this->anchors;
    }

    /**
     * @param AnchorBuilderOptions|null $anchors
     *
     * @return YamlDumperOptions
     */
    public function setAnchors(?AnchorBuilderOptions $anchors): YamlDumperOptions
    {
        $this->anchors = $anchors;

        return $this;
    }

}
