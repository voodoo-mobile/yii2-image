<?php

namespace vm\image\placeholders;

use vm\image\Placeholder;

/** @noinspection SpellCheckingInspection */

/**
 * Class PlaceholdIt
 * @package vm\image\placeholders
 *
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