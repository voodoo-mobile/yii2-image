<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 01:30
 */

namespace vr\image;

use vr\image\sources\ImageSource;

/**
 * Class ActiveImageTrait
 * @package vr\image
 */
trait ActiveImageTrait
{

    /**
     * @param string $attribute
     * @param int|int[]|string|null $dimension
     * @param bool $utm
     * @return mixed|null|string
     */
    public function url($attribute, $dimension = null, $utm = false)
    {
        return $this->getBehaviour()->url($attribute, $dimension, $utm);
    }

    /**
     * @return ImageBehavior
     */
    private function getBehaviour()
    {
        $this->ensureBehaviors();

        foreach ($this->behaviors as $behavior) {
            if (is_a($behavior, ImageBehavior::className())) {
                return $behavior;
            }
        }

        return null;
    }

    /**
     * @param string $attribute
     * @param ImageSource $source
     * @return bool
     */
    public function upload($attribute, $source)
    {
        return $this->getBehaviour()->upload($attribute, $source);
    }
}