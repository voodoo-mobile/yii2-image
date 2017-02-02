<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:16
 */

namespace vr\image;


use vr\image\connectors\FileSystemDataConnector;
use vr\image\filters\ResizeFilter;
use vr\image\placeholders\Placeholder;
use yii\base\Object;

/**
 * Class ImageDescriptor
 *
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
     *
     */
    public function init()
    {
        if (!$this->connector) {
            $this->connector = ['class' => FileSystemDataConnector::className()];
        }

        parent::init();
    }

    /**
     * @param null $dimension
     * @return mixed
     */
    public function getPlaceholderUrl($dimension = null)
    {
        /** @var Placeholder $placeholder */
        $placeholder = \Yii::createObject($this->placeholder);

        $width = $height = ImageBehavior::DEFAULT_IMAGE_DIMENSION;

        if ($dimension) {
            list($width, $height) = Utils::parseDimension($dimension);
        } else {
            /** @var ResizeFilter $filter */
            $filter = $this->findResizeFilter();

            if ($filter) {
                list($width, $height) = Utils::parseDimension($filter->dimension);
            }
        }

        return $placeholder->getImage($width, $height);
    }

    /**
     * @return null|object
     */
    private function findResizeFilter()
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