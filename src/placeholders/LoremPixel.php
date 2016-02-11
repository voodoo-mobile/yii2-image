<?php

namespace vm\image\placeholders;

use vm\image\Placeholder;

/** @noinspection SpellCheckingInspection */

/**
 * Class LoremPixel
 * @package vm\image\placeholders
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