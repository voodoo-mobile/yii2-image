<?php

namespace vr\image\placeholders;

use vr\image\Placeholder;

/** @noinspection SpellCheckingInspection */

/**
 * Class LoremPixel
 * @package vr\image\placeholders
 *
 *          Default implementation of Placeholder using Lorem Pixel service for generating placeholders
 */
class LoremPixel extends Placeholder
{
    /**
     *
     */
    public function init()
    {
        parent::init();

        if (!$this->value) {
            $this->value = function ($width, $height) {
                /** @noinspection SpellCheckingInspection */
                return sprintf('http://lorempixel.com/%d/%d', $width, $height);
            };
        }
    }

}