<?php

namespace vr\image\placeholders;

use vr\image\Placeholder;

/**
 * Class PlaceholdIt
 * @package vr\image\placeholders
 *          Default implementation of Placeholder using https://placehold.it service for generating placeholders
 */
class PlaceholdIt extends Placeholder
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
                return sprintf('https://placehold.it/%dx%d', $width, $height);
            };
        }
    }

}