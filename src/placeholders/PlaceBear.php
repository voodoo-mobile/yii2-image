<?php

namespace vr\image\placeholders\Placeholders;

use vr\image\placeholders\Placeholder;

/**
 * Class PlaceBear
 * @package vr\image\placeholders\Placeholders
 *          Default implementation of Placeholder using http://placebear.com service for generating placeholders
 */
class PlaceBear extends Placeholder
{
    public $isGray = false;

    /**
     *
     */
    public function init()
    {
        parent::init();

        if (!$this->value) {
            $this->value = function ($width, $height) {
                /** @noinspection SpellCheckingInspection */
                return sprintf($this->isGray ? 'http://placebear.com/g/%d/%d' : 'http://placebear.com/%d/%d',
                    $width, $height);
            };
        }
    }

}