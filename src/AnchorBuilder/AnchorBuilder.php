<?php


namespace DragoonBoots\YamlFormatter\AnchorBuilder;

use Ds\Map;
use Ds\Vector;

/**
 * Search the data for repeated data
 */
class AnchorBuilder
{
    /**
     * @var AnchorBuilderOptions
     */
    private $options;

    /**
     * AnchorBuilder constructor.
     *
     * @param AnchorBuilderOptions|null $options
     */
    public function __construct(?AnchorBuilderOptions $options = null)
    {
        $this->options = $options ?? new AnchorBuilderOptions();
    }

    /**
     * Determine which dataset members should be used as anchors
     *
     * @param iterable $data
     *
     * @return array
     */
    public function buildAnchors(iterable $data): array
    {
        $anchors = new Map();
        $tempAnchors = new Map();
        $path = new Vector();
        $this->compile($data, $anchors, $tempAnchors, $path);

        $ret = [];
        /** @var Vector $anchor */
        foreach ($anchors as $anchor => $value) {
            $anchor = $anchor->join('.');
            $ret[$anchor] = $value;
        }

        return $ret;
    }

    private function compile(iterable $data, Map &$useAnchors, Map &$anchors, Vector $path): Map
    {
        foreach ($data as $key => $value) {
            $valuePath = clone $path;
            $valuePath->push($key);
            $anchorKey = $valuePath->join('.');

            // Should an anchor be built for this path?
            $buildAnchor = $this->includePath($anchorKey) && !$this->excludePath($anchorKey);
            if (!$buildAnchor) {
                continue;
            }

            // Use the anchor if this is an array or the final key in the key
            // path matches (this means these values are likely similar
            // contextually.
            $useAnchor = $this->useAnchor($value, $valuePath, $anchors);

            if ($useAnchor !== null) {
                $useAnchors[$useAnchor] = $value;
            } else {
                $anchors[$valuePath] = $value;
                if (is_iterable($value) && !is_string($value)) {
                    $this->compile($value, $useAnchors, $anchors, $valuePath);
                }
            }
        }

        return $useAnchors;
    }

    private function includePath(string $path): bool
    {
        if (empty($this->options->getInclude())) {
            // Default to including everything
            return true;
        }

        foreach ($this->options->getInclude() as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private function excludePath(string $path): bool
    {
        foreach ($this->options->getExclude() as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the data is similar enough to merit using an anchor.
     *
     * Contextually similar means the value is an iterable that matches other data, or is a scalar
     * with a matching final key.
     *
     * @param mixed $value
     * @param Vector $valuePath
     * @param Map $anchors
     *
     * @return Vector|null
     */
    private function useAnchor($value, Vector $valuePath, Map $anchors): ?Vector
    {
        /** @var Vector $checkAnchorKey */
        foreach ($anchors as $checkAnchorKey => $checkValue) {
            $sameValue = $checkValue === $value;
            $similarPath = $checkAnchorKey->last() === $valuePath->last();
            if ($sameValue && ((is_iterable($value) && !is_string($value)) || $similarPath)) {
                return $checkAnchorKey;
            }
        }

        return null;
    }
}
