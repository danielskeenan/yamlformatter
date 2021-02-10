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
     * Merge in config
     *
     * @param array $options
     *
     * @return $this
     */
    public function merge(array $options): AnchorBuilderOptions
    {
        $setters = [
            'include' => [$this, 'setInclude'],
            'exclude' => [$this, 'setExclude'],
        ];
        foreach ($setters as $prop => $setter) {
            if (isset($options[$prop])) {
                call_user_func($setter, $options[$prop]);
            }
        }

        return $this;
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
