<?php

namespace vr\image\placeholders;

/** @noinspection SpellCheckingInspection */

/**
 * Class LoremPixel
 * @package vr\image\placeholders\Placeholders
 *          Default implementation of Placeholder using Lorem Pixel service for generating placeholders
 */
class LoremPixel extends Placeholder
{
    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function getImageUrl($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT)
    {
        return "https://lorempixel.com/{$width}/{$height}";
    }
}