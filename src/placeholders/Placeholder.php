<?php

namespace vr\image\placeholders;

use yii\base\Component;

/**
 * Class Placeholder
 * @package vr\image
 *          Provides the way to return a placeholder.
 *          Use: implement [[getImageUrl]]
 *          This class is used in ImageBehavior. In most cases no need to use it directly
 */
abstract class Placeholder extends Component
{
    /**
     * Default placeholder size
     */
    const DEFAULT_SIZE = 320;

    /**
     *
     */
    const USE_ALWAYS = 0xF;

    /**
     *
     */
    const USE_IF_MISSING = 0x1;

    /**
     *
     */
    const USE_IF_NULL = 0x2;

    /**
     *
     */
    const DEFAULT_WIDTH = 640;

    /**
     *
     */
    const DEFAULT_HEIGHT = 480;

    /** @var bool */
    public $onlyNotExist = false;

    /**
     * @param int $width
     * @param int $height
     *
     * @return bool|string
     */
    public function getImage($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT)
    {
        return file_get_contents($this->getImageUrl($width, $height));
    }

    /**
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    abstract public function getImageUrl($width = self::DEFAULT_WIDTH, $height = self::DEFAULT_HEIGHT);
}