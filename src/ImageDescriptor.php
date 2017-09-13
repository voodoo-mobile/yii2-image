<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:16
 */

namespace vr\image;

use vr\image\connectors\DataConnector;
use vr\image\connectors\FileSystemDataConnector;
use vr\image\filters\ResizeFilter;
use vr\image\placeholders\Placeholder;
use vr\image\sources\UrlSource;
use yii\base\Model;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class ImageDescriptor
 * @package vr\image
 * @property Model $model
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
     * @var array
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
    private $model;

    /**
     *
     */
    public function init()
    {
        if (!$this->connector) {
            $this->connector = ['class' => FileSystemDataConnector::className()];
        }

        /** @noinspection PhpDeprecationInspection */
        parent::init();
    }

    /**
     * @param           $attribute
     * @param           $dimension
     * @param bool      $utm
     *
     * @return mixed|string
     */
    public function url($attribute, $dimension = null, $utm = true)
    {
        /** @var DataConnector $connector */
        $connector = $this->createConnector();

        $filename = $this->model->{$attribute};

        if (!empty($filename) && !empty($dimension) && $connector->exists($filename)) {
            $filename = $this->createThumbnail($connector, $filename, $dimension);
        }

        $url = $connector->url($filename, $utm);

        if ($this->placeholder) {
            $url = $this->applyPlaceholder($url, $dimension);
        }

        return $url;
    }

    /**
     * @return object
     */
    private function createConnector()
    {
        $class = (new \ReflectionClass($this->model))->getShortName();

        return \Yii::createObject($this->connector + [
                'folder' => Inflector::camel2id($class, '-'),
            ]
        );
    }

    /**
     * @param DataConnector $connector
     * @param               $filename
     * @param               $dimension
     *
     * @return string
     */
    private function createThumbnail($connector, $filename, $dimension)
    {
        $thumbnail = $this->getThumbnailFilename($filename, $dimension);

        if (!$connector->exists($thumbnail)) {
            $source = new UrlSource([
                'url' => $connector->url($filename),
            ]);

            $mediator = $source->createMediator();
            (new ResizeFilter([
                'dimension' => $dimension,
            ]))->apply($mediator);

            $connector->upload($mediator, $thumbnail);
        }

        return $thumbnail;
    }

    /**
     * @param $filename
     * @param $dimension
     *
     * @return string
     */
    private function getThumbnailFilename($filename, $dimension)
    {
        $info = pathinfo($filename);

        list($width, $height) = Utils::parseDimension($dimension);

        return ArrayHelper::getValue($info, 'filename') . "-{$width}x{$height}." .
               ArrayHelper::getValue($info, 'extension');
    }

    /**
     * @param $url
     * @param $dimension
     *
     * @return mixed
     */
    public function applyPlaceholder($url, $dimension = null)
    {
        /** @var Placeholder $placeholder */
        $placeholder = \Yii::createObject($this->placeholder);
        list($width, $height) = Utils::parseDimension($dimension);

        if (!$width || !$height) {
            /** @var ResizeFilter $filter */
            $filter = $this->findResizeFilter();

            if ($filter) {
                list($width, $height) = Utils::parseDimension($filter->dimension);
            }
        }

        if (!$width || !$height) {
            $width = $height = ImageBehavior::DEFAULT_IMAGE_DIMENSION;
        }

        if (($placeholder->useWhen & Placeholder::USE_IF_NULL) && empty($url)) {
            return $placeholder->getImageUrl($width, $height);
        }

        if (($placeholder->useWhen & Placeholder::USE_IF_MISSING) && !$this->isUrlValid($url)) {
            return $placeholder->getImageUrl($width, $height);
        }

        return $url;
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
     *
     * @return bool
     */
    private function isUrlValid($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $headers = get_headers($url);

        return strpos($headers[0], '200') !== false;
    }

    /**
     * @param Mediator $mediator
     *
     * @return bool
     */
    public function upload(Mediator $mediator)
    {
        foreach ($this->filters as $name => $filter) {
            \Yii::createObject($filter)->apply($mediator);
        }

        /** @var DataConnector $connector */
        $connector = $this->createConnector();

        $filename = $this->getFilename($mediator->extension);

        if (($existing = $this->model->{$this->attribute})) {
            $connector->drop($existing);
        }

        if (!$connector->upload($mediator, $filename)) {
            $this->model->addError($this->attribute, $connector->lastError);

            return false;
        }

        $this->model->{$this->attribute} = $filename;

        return true;
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

        $replacements = [];

        foreach ($this->getBaseAttributes() as $attribute) {
            $replacements["{.$attribute}"] = ArrayHelper::getValue($this->model, $attribute);
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

    public function getBaseAttributes()
    {
        preg_match_all('/{\.(.*?)}/', $this->template, $matches);

        $attributes = ArrayHelper::getValue($matches, 1);

        if (!$attributes || !is_array($attributes)) {
            $attributes = [];
        }

        return $attributes;
    }

    /**
     *
     */
    public function onAfterDelete()
    {
        $connector = $this->createConnector();

        if ($connector->drop($this->model->{$this->attribute})) {
            $this->model->{$this->attribute} = null;
        };
    }

    /**
     *
     */
    public function onBeforeUpdate()
    {
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     *
     */
    public function onAfterUpdate()
    {
        $connector = $this->createConnector();
        $source    = $this->model->{$this->attribute};

        if ($source) {
            $this->model->{$this->attribute} = ($destination = $this->getFilename());
            $connector->rename($source, $destination);
        }
    }
}