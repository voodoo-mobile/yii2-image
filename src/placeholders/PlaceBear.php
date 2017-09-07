<?php

namespace vr\image\placeholders;

/**
 * Class PlaceBear
 * @package vr\image\placeholders\Placeholders
 *          Default implementation of Placeholder using http://placebear.com service for generating placeholders
 */
class PlaceBear extends Placeholder
{
    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function getImageUrl($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT)
    {
        return "https://placebear.com/{$width}/{$height}";
    }
}