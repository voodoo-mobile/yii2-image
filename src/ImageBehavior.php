<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 01:47
 */

namespace vr\image;


use vr\image\connectors\DataConnector;
use vr\image\filters\Filter;
use vr\image\filters\ResizeFilter;
use vr\image\sources\ImageSource;
use vr\image\sources\UrlSource;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class ImageBehavior
 * @package vr\image
 *
 *
 * Usage: Add this to your model class
 *
 *  1. Add this to your behaviours() method
 *
 *  public function behaviors()
 *  {
 *      return [
 *          [
 *              'class' => ImageBehavior::className(),
 *              'imageAttributes' => [
 *                  'image' => [
 *                      'basedOn' => 'title',
 *                      'connector' => [
 *                          'class' => FileSystemDataConnector::className(),
 *                      ],
 *                      'placeholder' => [
 *                          'class' => PlaceBear::className()
 *                      ],
 *                      'filters' => [
 *                          'resize' => [
 *                              'class' => ResizeFilter::className(),
 *                              'dimension' => [100, 200]
 *                          ],
 *                      ]
 *                  ]
 *              ],
 *          ],
 *      ];
 *  }
 *
 *  2. Don't forget to add ActiveImageTrait to your class to define missing functions
 *  3. Add this code to the model where you upload your image
 *
 *      if (($instance = UploadedFile::getInstance($this, 'image'))) {
 *          $product->upload('image', new UploadedFileSource([
 *              'uploaded' => $instance
 *          ]));
 *      }
 *
 */
class ImageBehavior extends Behavior
{
    /**
     *
     */
    const DEFAULT_IMAGE_DIMENSION = 320;
    /**
     * @var
     */
    public $imageAttributes;
    /**
     * @var
     */
    public $descriptors;
    /**
     * @var bool
     */
    public $skipUpdateOnClean = !YII_DEBUG;

    /**
     *
     */
    public function init()
    {
        parent::init();

        if (!is_array($this->imageAttributes)) {
            $this->imageAttributes = [$this->imageAttributes];
        }

        foreach ($this->imageAttributes as $attribute => $params) {

            if (is_numeric($attribute)) {
                $attribute = $params;
                $params = [];
            }

            $this->descriptors[$attribute] = \Yii::createObject($params + [
                    'class' => ImageDescriptor::className(),
                    'attribute' => $attribute,
                ]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeUpdate',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'onAfterUpdate',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'onAfterDelete',
        ];
    }

    /**
     * Returns the qualified URI of the image
     *
     * @param $attribute
     *
     * @param $dimension
     * @param bool $utm
     * @return mixed|null|string URI of the image
     */
    public function url($attribute, $dimension = null, $utm = true)
    {
        /** @var ImageDescriptor $descriptor */
        $descriptor = $this->getDescriptor($attribute);

        /** @var DataConnector $connector */
        $connector = $this->createConnector($descriptor);

        $filename = $this->getActiveRecord()->getAttribute($attribute);

        if (!empty($filename) && !empty($dimension) && $connector->exists($filename)) {
            $filename = $this->createThumbnail($connector, $filename, $dimension);
        }

        $url = $connector->url($filename, $utm);

        if (empty($url) && $descriptor->placeholder) {
            $url = $descriptor->getPlaceholderUrl($dimension);
        }

        return $url;
    }

    /**
     * @param $attribute
     * @return ImageDescriptor
     */
    protected function getDescriptor($attribute)
    {
        return $this->descriptors[$attribute];
    }

    /**
     * @param $descriptor
     * @return object
     */
    private function createConnector($descriptor)
    {
        return \Yii::createObject($descriptor->connector + [
                'folder' => Inflector::slug((new \ReflectionClass($this->getActiveRecord()))->getShortName())
            ]
        );
    }

    /**
     * @return ActiveRecord
     */
    private function getActiveRecord()
    {
        return $this->owner;
    }

    /**
     * @param DataConnector $connector
     * @param $filename
     * @param $dimension
     * @return string
     */
    private function createThumbnail($connector, $filename, $dimension)
    {
        $thumbnail = $this->getThumbnailFilename($filename, $dimension);

        if (!$connector->exists($thumbnail)) {
            $source = new UrlSource([
                'url' => $connector->url($filename)
            ]);

            $mediator = $source->createMediator();
            (new ResizeFilter([
                'dimension' => $dimension
            ]))->apply($mediator);

            $connector->upload($mediator, $thumbnail);
        }

        return $thumbnail;
    }

    /**
     * @param $filename
     * @param $dimension
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
     * @param string $attribute
     * @param ImageSource $source
     * @return bool
     */
    public function upload($attribute, $source)
    {
        if (!$source || !$source->validate()) {
            return false;
        }

        /** @var Mediator $mediator */
        $mediator = $source->createMediator();

        /** @var Filter[] $filters */
        $descriptor = $this->getDescriptor($attribute);

        foreach ($descriptor->filters as $name => $filter) {
            \Yii::createObject($filter)->apply($mediator);
        }

        /** @var DataConnector $connector */
        $connector = $this->createConnector($descriptor);

        $filename = $this->getFilename($descriptor, $mediator->extension);

        if (($existing = $this->getActiveRecord()->getAttribute($attribute))) {
            $connector->drop($existing);
        }

        if (!$connector->upload($mediator, $filename)) {
            $this->getActiveRecord()->addError($descriptor->attribute, $connector->lastError);
            return false;
        }

        $this->owner->$attribute = $filename;

        return true;
    }

    /**
     * @param ImageDescriptor $descriptor
     * @param $extension
     * @return string
     */
    private function getFilename($descriptor, $extension)
    {
        $baseFilename = $descriptor->basedOn ? Inflector::slug($this->getActiveRecord()->{$descriptor->basedOn}) : md5(uniqid());

        if (empty($extension)) {
            $previous = $this->getActiveRecord()->getAttribute($descriptor->attribute);
            $extension = pathinfo($previous, PATHINFO_EXTENSION);
        }

        return "{$baseFilename}-{$descriptor->attribute}" . ($extension ? '.' . $extension : null);
    }

    /**
     *
     */
    public function onBeforeUpdate()
    {
        $activeRecord = $this->getActiveRecord();

        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $descriptor) {
            if (!$activeRecord->isAttributeChanged($descriptor->basedOn)) {
                continue;
            }

            $connector = $this->createConnector($descriptor);

            $source = $this->getActiveRecord()->getAttribute($descriptor->attribute);
            $destination = $this->getFilename($descriptor, null);

            $this->getActiveRecord()->setAttribute($descriptor->attribute, $destination);

            $connector->rename($source, $destination);
        }
    }

    public function onAfterUpdate()
    {

    }

    /**
     *
     */
    public function onAfterDelete()
    {
        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $descriptor) {

            /** @var DataConnector $connector */
            $connector = $this->createConnector($descriptor);

            if ($connector->drop($this->getActiveRecord()->getAttribute($descriptor->attribute))) {
                $this->getActiveRecord()->setAttribute($descriptor->attribute, null);
            };
        }
    }
}