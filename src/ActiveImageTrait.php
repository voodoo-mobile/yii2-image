<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 01:30
 */

namespace vr\image;

/**
 * Class ActiveImageTrait
 * @package vr\image
 */
trait ActiveImageTrait
{

    /**
     * @param $attribute
     * @return mixed|null|string
     */
    public function url($attribute)
    {
        return $this->getBehaviour()->url($attribute);
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
     * @param $attribute
     * @param $source
     * @return bool
     */
    public function upload($attribute, $source)
    {
        return $this->getBehaviour()->upload($attribute, $source);
    }
}