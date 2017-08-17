<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 01:30
 */

namespace vr\image;

use vr\image\sources\ImageSource;
use yii\base\Component;

/**
 * Class ActiveImageTrait
 * @package vr\image
 */
trait ActiveImageTrait
{

    /**
     * @param string                $attribute
     * @param int|int[]|string|null $dimension
     * @param bool                  $utm
     *
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
        /** @var $this Component */
        $this->ensureBehaviors();

        foreach ($this->behaviors as $behavior) {
            if (is_a($behavior, ImageBehavior::className())) {
                return $behavior;
            }
        }

        return null;
    }

    /**
     * @param string      $attribute
     * @param ImageSource $source
     * @param array       $options
     *  Following options are supported:
     *  - defaultExtension. If set and the web service cannot determine the extension automatically based on the
     *  binary content this extension will be used. Otherwise it will be ignored. It can be used for some content
     *  files which are not described properly in the web service configuration files.
     *
     * @return bool
     */
    public function upload($attribute, $source, $options = [])
    {
        return $this->getBehaviour()->upload($attribute, $source, $options);
    }
}