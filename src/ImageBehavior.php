<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 01:47
 */

namespace vr\image;

use vr\image\sources\ImageSource;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

/**
 * Class ImageBehavior
 * @package vr\image
 * Usage:
 *  1. Add this to your model class
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
 *  2. Don't forget to add ActiveImageTrait to your class to define missing functions
 *  3. Add this code to the model where you upload your image
 *      if (($instance = UploadedFile::getInstance($this, 'image'))) {
 *          $product->upload('image', new UploadedFileSource([
 *              'uploaded' => $instance
 *          ]));
 *      }
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

    private $changeQueue = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_INIT          => 'onInit',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeUpdate',
            BaseActiveRecord::EVENT_AFTER_UPDATE  => 'onAfterUpdate',
            BaseActiveRecord::EVENT_AFTER_DELETE  => 'onAfterDelete',
        ];
    }

    /**
     * Returns the qualified URI of the image
     *
     * @param      $attribute
     * @param      $dimension
     * @param bool $utm
     *
     * @return mixed|null|string URI of the image
     */
    public function url($attribute, $dimension = null, $utm = true)
    {
        /** @var ImageDescriptor $descriptor */
        $descriptor = $this->getDescriptor($attribute);

        return $descriptor->url($attribute, $dimension, $utm);
    }

    /**
     * @param $attribute
     *
     * @return ImageDescriptor
     */
    protected function getDescriptor($attribute)
    {
        return $this->descriptors[$attribute];
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
        if (!$source || !$source->validate()) {
            return false;
        }

        /** @var Mediator $mediator */
        $mediator = $source->createMediator();
        $mediator->setOptions($options);

        $descriptor = $this->getDescriptor($attribute);

        return $descriptor->upload($mediator);
    }

    /**
     *
     */
    public function onInit()
    {
        if (!is_array($this->imageAttributes)) {
            $this->imageAttributes = [$this->imageAttributes];
        }

        foreach ($this->imageAttributes as $attribute => $params) {

            if (is_numeric($attribute)) {
                $attribute = $params;
                $params    = [];
            }

            /** @var ImageDescriptor $descriptor */
            $descriptor = \Yii::createObject($params + [
                    'class'     => ImageDescriptor::className(),
                    'attribute' => $attribute,
                    'model'     => $this->owner,
                ]
            );

            $this->descriptors[$attribute] = $descriptor;
        }
    }

    /**
     *
     */
    public function onBeforeUpdate()
    {
        $this->changeQueue = [];

        /** @var ActiveRecord $activeRecord */
        $activeRecord = $this->owner;

        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $attribute => $descriptor) {
            $dirtyAttributes = $activeRecord->getDirtyAttributes($descriptor->getBaseAttributes());

            if (count($dirtyAttributes)) {
                $this->changeQueue[] = $attribute;
            }

            $descriptor->onBeforeUpdate();
        }
    }

    /**
     *
     */
    public function onAfterUpdate()
    {
        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $attribute => $descriptor) {
            if (in_array($attribute, $this->changeQueue)) {
                $descriptor->onAfterUpdate();
            }
        }
    }

    /**
     *
     */
    public function onAfterDelete()
    {
        /** @var ImageDescriptor $descriptor */
        foreach ($this->descriptors as $descriptor) {
            $descriptor->onAfterDelete();
        }
    }
}