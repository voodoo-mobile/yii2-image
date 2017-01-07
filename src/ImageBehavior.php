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
use vr\image\placeholders\Placeholder;
use vr\image\sources\ImageSource;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;

/**
 * Class ImageBehavior
 * @package vr\image
 *
 *
 * Usage: Add this to your model class
 *
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
 *  Don't forget to add ActiveImageTrait to your class to define missing functions
 *
 */
class ImageBehavior extends Behavior
{
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
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'rename',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'delete',
        ];
    }

    /**
     * Returns the qualified URI of the image
     *
     * @param $attribute
     *
     * @param bool $utm
     * @return mixed|null|string URI of the image
     */
    public function url($attribute, $utm = true)
    {
        /** @var ImageDescriptor $descriptor */
        $descriptor = $this->getDescriptor($attribute);

        $filename = $this->getActiveRecord()->getAttribute($attribute);
        $url = $this->createConnector($descriptor)->url($filename, $utm);

        if (empty($url) && $descriptor->placeholder) {

            /** @var Placeholder $placeholder */
            $placeholder = \Yii::createObject($descriptor->placeholder);

            /** @var ResizeFilter $filter */
            $filter = $descriptor->findResizeFilter();

            $width = $height = self::DEFAULT_IMAGE_DIMENSION;

            if ($filter) {
                list($width, $height) = $filter->getDimensions($filter->dimension);
            }

            return $placeholder->getImage($width, $height);
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
     * @return ActiveRecord
     */
    private function getActiveRecord()
    {
        return $this->owner;
    }

    /**
     * @param $descriptor
     * @return object
     */
    private function createConnector($descriptor)
    {
        return \Yii::createObject($descriptor->connector + [
                'category' => Inflector::slug((new \ReflectionClass($this->getActiveRecord()))->getShortName())
            ]);
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

        return "{$baseFilename}-{$descriptor->attribute}" . ($extension ? '.' . $extension : null);
    }

    /**
     *
     */
    public function rename()
    {
        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $descriptor) {

            /** @var DataConnector $connector */
            $connector = $this->createConnector($descriptor);

            $filename = $this->getFilename($descriptor, null);

            if ($filename = $connector->rename($this->getActiveRecord()->getAttribute($descriptor->attribute), $filename)) {
                $this->getActiveRecord()->setAttribute($descriptor->attribute, $filename);
            };
        }
    }

    /**
     *
     */
    public function delete()
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