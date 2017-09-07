<?php

namespace vr\image\placeholders;

/**
 * Class UrlPlaceholder
 * @package vr\image\placeholders\Placeholders
 */
class UrlPlaceholder extends Placeholder
{
    /** @var string */
    public $url;

    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function getImageUrl($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT)
    {
        return $this->url;
    }
}