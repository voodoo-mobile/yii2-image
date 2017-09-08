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
use yii\base\Model;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

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
     * @var string
     */
    public $template = '{attribute}-{datetime}-{tag}';

    /**
     * @var Model
     */
    public $model;

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
     *
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
     * @param $url
     * @param $dimension
     *
     * @return mixed
     */
    public function applyPlaceholder($url, $dimension)
    {
        /** @var Placeholder $placeholder */
        $placeholder = \Yii::createObject($this->placeholder);

        list($width, $height) = Utils::parseDimension($dimension);

        if (($placeholder->useWhen & Placeholder::USE_IF_NULL) && empty($url)) {
            return $placeholder->getImageUrl($width, $height);
        }

        if (($placeholder->useWhen & Placeholder::USE_IF_MISSING) && !$this->isUrlValid($url)) {
            return $placeholder->getImageUrl($width, $height);
        }

        return $url;
    }

    private function isUrlValid($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $headers = get_headers($url);

        return strpos($headers[0], '200') !== false;
    }

    /**
     * @param $extension
     *
     * @return string
     */
    public function getFilename($extension = null)
    {

        if (empty($extension)) {
            $previous  = ArrayHelper::getValue($this->model, $this->attribute);
            $extension = pathinfo($previous, PATHINFO_EXTENSION);
        }

        return $this->getBasename() . ($extension ? '.' . $extension : null);
    }

    /**
     * @return mixed|string
     */
    public function getBasename()
    {
        $datetime = new \DateTime();

        preg_match_all('/{\.(.*?)}/', $this->template, $matches);

        $replacements = [];

        $attributes = ArrayHelper::getValue($matches, 1);

        if ($attributes && is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $replacements["{.$attribute}"] = ArrayHelper::getValue($this->model, $attribute);
            }
        }

        $replacements += [
            '{tag}'       => crc32(uniqid()),
            '{datetime}'  => $datetime->format('Y-m-d-H-i-s'),
            '{time}'      => $datetime->format('H-i-s'),
            '{date}'      => $datetime->format('Y-m-d'),
            '{attribute}' => $this->attribute,
        ];

        $basename = strtr($this->template, $replacements);

        return Inflector::slug($basename);
    }
}