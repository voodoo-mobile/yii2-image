<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:16
 */

namespace vr\image;


use vr\image\filters\ResizeFilter;
use yii\base\Object;

/**
 * Class ImageDescriptor
 * @package vr\image
 */
class ImageDescriptor extends Object
{
    /**
     * @var array
     */
    public $filters = [];

    /**
     * @var
     */
    public $attribute;

    /**
     * @var
     */
    public $connector;

    /**
     * @var
     */
    public $placeholder;

    /**
     * @var null
     */
    public $basedOn = null;

    /**
     * @return null|object
     */
    public function findResizeFilter()
    {
        foreach ($this->filters as $filter) {
            $instance = \Yii::createObject($filter);
            if (is_a($instance, ResizeFilter::className())) {
                return $instance;
            }
        }

        return null;
    }
}