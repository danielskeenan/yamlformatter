<?php


namespace DragoonBoots\YamlFormatter\AnchorBuilder;

/**
 * Options for AnchorBuilder.
 */
final class AnchorBuilderOptions
{
    /**
     * Regular expression of paths to include.  Defaults to including everything.
     *
     * YAML keys are separated by periods.
     *
     * @var array
     */
    private $include;

    /**
     * Regular expression of paths to exclude.  This overrides $include.
     *
     * YAML keys are separated by periods.
     *
     * @var array
     */
    private $exclude;

    public function __construct(array $include = [], array $exclude = [])
    {
        $this->include = $include;
        $this->exclude = $exclude;
    }

    /**
     * @return array
     */
    public function getInclude(): array
    {
        return $this->include;
    }

    /**
     * @param array $include
     *
     * @return AnchorBuilderOptions
     */
    public function setInclude(array $include): AnchorBuilderOptions
    {
        $this->include = $include;

        return $this;
    }

    /**
     * @return array
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * @param array $exclude
     *
     * @return AnchorBuilderOptions
     */
    public function setExclude(array $exclude): AnchorBuilderOptions
    {
        $this->exclude = $exclude;

        return $this;
    }

}
