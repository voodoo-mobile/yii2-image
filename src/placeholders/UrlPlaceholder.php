<?php
namespace vr\image\placeholders\Placeholders;

use vr\image\placeholders\Placeholder;

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
     * @return string
     */
    public function getImage($width, $height)
    {
        return $this->url;
    }
}