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
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

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
     * @var
     */
    public $timestamp;

    /**
     * @var
     */
    public $tag;

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

    /**
     * @param $model
     * @return mixed|string
     */
    public function getBasename($model)
    {
        $basename = crc32(uniqid());

        if ($this->basedOn) {
            $expression = $this->basedOn;

            if (is_callable($expression)) {
                $expression = call_user_func($expression, $model);
            }

            if (is_array($expression)) {

                $values = ArrayHelper::getColumn($expression, function ($item) use ($model) {
                    return $this->evaluate($model, $item);
                });

                $basename = implode('-', $values);
            }


            if (is_string($expression)) {
                $basename = $this->evaluate($model, $expression);
            }
        }

        if ($this->timestamp) {
            $basename .= '-' . (new \DateTime())->format($this->timestamp);
        }

        if ($this->tag && $this->basedOn) {
            $basename .= '-' . crc32(uniqid());
        }

        return $basename;
    }

    /**
     * @param $model
     * @param $expression
     * @return mixed|string
     */
    private function evaluate($model, $expression)
    {
        $basename = $model;

        foreach (explode('.', $expression) as $item) {
            $basename = ArrayHelper::getValue($basename, $item);
        }

        return Inflector::slug($basename);
    }
}