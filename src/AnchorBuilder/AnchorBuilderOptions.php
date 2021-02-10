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
     */
    public array $include = [];

    /**
     * Regular expression of paths to exclude.  This overrides $include.
     *
     * YAML keys are separated by periods.
     */
    public array $exclude = [];
}
