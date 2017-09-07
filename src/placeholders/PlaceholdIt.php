<?php

namespace vr\image\placeholders;

/**
 * Class PlaceholdIt
 * @package vr\image\placeholders\Placeholders
 *          Default implementation of Placeholder using https://placehold.it service for generating placeholders
 */
class PlaceholdIt extends Placeholder
{
    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function getImageUrl($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT)
    {
        return "https://placehold.it/{$width}x{$height}";
    }
}