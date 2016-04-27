<?php
namespace vm\image\placeholders;

use vm\image\Placeholder;

class UrlPlaceholder extends Placeholder
{
    /** @var string */
    public $url;

    public function getImage($width, $height)
    {
        return $this->url;
    }
}