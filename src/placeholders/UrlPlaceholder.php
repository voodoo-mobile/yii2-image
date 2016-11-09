<?php
namespace vr\image\placeholders;

use vr\image\Placeholder;

/**
 * Class UrlPlaceholder
 * @package vr\image\placeholders
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