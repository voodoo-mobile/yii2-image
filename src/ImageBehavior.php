<?php
namespace vr\image;

use Yii;
use yii\base\Exception;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

/** @noinspection SpellCheckingInspection */

/**
 * Class ImageBehavior
 * @package vr\image
 * @property string $sourceAttribute
 *          Behavior for manipulating images
 *          public function behaviors() {
 *
 *          }
 */
class ImageBehavior extends AttributeBehavior
{
    /**
     * @var array[]
     */
    public $imageAttributes = [];

    /**
     * @var string. Suffix of the variable that is linked to the content. This variable will be used for forms and
     *      code. For example if the image attribute is image then source attribute will be image_source
     */
    public $suffix = '_source';

    /**
     * @var bool It is used only for internal process optimization. Please don't pay your attention to it
     */
    private $saving = false;

    /**
     * @var null
     */
    private $descriptors = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_AFTER_INSERT => $this->imageAttributes,
                BaseActiveRecord::EVENT_AFTER_UPDATE => $this->imageAttributes,
            ];

            foreach ($this->imageAttributes as $attribute => $params) {
                $this->descriptors[$attribute] = Yii::createObject($params + [
                        'class'           => '\vr\image\ImageAttributeDescriptor',
                        'attribute'       => $attribute,
                        'sourceAttribute' => $attribute . $this->suffix
                    ]
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function evaluateAttributes($event)
    {
        if (!$this->saving) {

            /** @var ActiveRecord $owner */
            $owner = $this->owner;

            /** @var ImageAttributeDescriptor $descriptor */
            foreach ($this->descriptors as $descriptor) {
                $owner->{$descriptor->attribute} = $descriptor->getValue($owner);
            }

            $this->saving = true;

            if (!$owner->save(false)) {
                throw new Exception('Cannot save model because of: ' . var_export($owner->errors));
            }

            $this->saving = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($attribute = $this->getBaseAttribute($name)) {
            return $this->descriptors[$attribute]->source;
        }

        return $this->owner->__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($attribute = $this->getBaseAttribute($name)) {
            $this->descriptors[$attribute]->source = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @param $name
     * @return int|null|string
     */
    private function getBaseAttribute($name)
    {
        foreach ($this->imageAttributes as $attribute => $params) {
            if ($attribute . $this->suffix == $name) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return parent::canSetProperty($name, $checkVars) || $this->isSourceAttribute($name);
    }

    /**
     * @inheritdoc
     */
    private function isSourceAttribute($name)
    {
        return $this->getBaseAttribute($name) != null;
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return parent::canGetProperty($name, $checkVars) || $this->isSourceAttribute($name);
    }

    /**
     * Returns a thumbnail for the image. It will create it the thumbnail is missing or reuse existing otherwise
     *
     * @param      $attribute
     * @param      $dimension . Desired dimension of the thumbnail. For example 120 or [120, 120]
     * @param bool $absoluteUrl
     *
     * @return mixed|null|string
     */
    public function thumbnail($attribute, $dimension, $absoluteUrl = true)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->descriptors[$attribute]->thumbnail($this->owner, $dimension, $absoluteUrl);
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
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->descriptors[$attribute]->url($this->owner, $utm);
    }
}
