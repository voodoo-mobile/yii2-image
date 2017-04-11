<?php

namespace vr\image\placeholders;

use yii\base\Component;

/**
 * Class Placeholder
 * @package vr\image
 *          Provides the way to return a placeholder.
 *          Use:
 *              set up value as function($width, $height) {
 *                                  return 'http://imagegenerator.com/width/height';
 *                              }
 *          This class is used in ImageBehavior. In most cases no need to use it directly
 */
class Placeholder extends Component
{
    /**
     * Default placeholder size
     */
    const DEFAULT_SIZE = 320;

    /**
     * @var
     */
    public $value;

    /** @var bool  */
    public $onlyNotExist = false;

    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function getImage($width, $height)
    {
        return call_user_func($this->value, $width, $height);
    }
}