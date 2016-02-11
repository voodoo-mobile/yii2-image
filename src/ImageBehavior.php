<?php
namespace vm\image;

use vm\upload\Base64Source;
use vm\upload\FileWriter;
use vm\upload\ModelSource;
use vm\upload\Source;
use Yii;
use yii\base\Exception;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\Url;
use yii\validators\UrlValidator;
use yii\web\UploadedFile;

/** @noinspection SpellCheckingInspection */

/**
 * Class ImageBehavior
 * @package vm\image
 *
 * @property string $sourceAttribute
 *
 *          Behavior for manipulating images
 *
 *          public function behaviors()
 *          {
 *              return [
 *                  [
 *                      'class'          => ImageBehavior::className(),
 *                      'imageAttribute' => 'image',
 *                      'resize'         => [640, 640],
 *                      'crop'           => true,
 *                      'placeholder'    => [
 *                          'class' => LoremPixelPlaceholder::className()
 *                      ]
 *                  ],
 *              ];
 *          }
 */
class ImageBehavior extends AttributeBehavior
{
    /**
     * @var string. Attribute that contains the image path / url
     */
    public $imageAttribute = 'image';

    /**
     * @var int[] | int | null. Determines if the image needs to be resized when uploading or updating
     */
    public $resize = [Placeholder::DEFAULT_SIZE, Placeholder::DEFAULT_SIZE];

    /**
     * @var bool. Determines whether the image need to be cropped when it is resized. False by default
     */
    public $crop = false;

    /**
     * @var \Closure | null. Placeholder factory that generates placeholder images in case of any problems with the real image
     */
    public $placeholder;

    /**
     * @var string. Suffix of the variable that is linked to the content. This variable will be used for forms and code. For
     *      example if the image attribute is image then source attribute will be image_source
     */
    public $suffix = '_source';

    /**
     * @var mixed Variable that contains the content. It can be base64 or [\yii\web\UploadedFile]
     */
    private $source = null;

    /**
     * @var bool It is used only for internal process optimization. Please don't pay your attention to it
     */
    private $saving = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_AFTER_INSERT => $this->imageAttribute,
                BaseActiveRecord::EVENT_AFTER_UPDATE => $this->imageAttribute
            ];
        }

        if ($this->placeholder) {
            $this->placeholder = Yii::createObject($this->placeholder);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        if ($this->saving) {
            return $owner->{$this->imageAttribute};
        }

        /** @var Source $source */
        $source = null;

        if (is_string($this->source) && strlen($this->source)) {
            $source = Base64Source::create($this->source);
        } elseif ($instance = UploadedFile::getInstance($owner, $this->getSourceAttribute())) {
            $source = ModelSource::create($owner, $this->getSourceAttribute());
        }

        if (!$source) {
            return $owner->{$this->imageAttribute};
        }

        /** @var UploadedImage $uploaded */
        $uploaded = new UploadedImage([
            'source' => $source
        ]);

        if ($this->resize) {
            $uploaded->resize($this->resize, $this->crop);
        }

        $writer = (new FileWriter())->useActiveRecord($owner, $this->imageAttribute);
        $path   = $uploaded->save($writer);

        if (!$this->saving) {
            (new Thumbnailer(['imagePath' => $path]))->clear();
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    public function evaluateAttributes($event)
    {
        if (!$this->saving) {
            parent::evaluateAttributes($event);

            $this->saving = true;

            /** @var ActiveRecord $owner */
            $owner = $this->owner;

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
        return $this->isSourceAttribute($name) ? $this->source : $this->owner->__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->isSourceAttribute($name)) {
            $this->source = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Returns a thumbnail for the image. It will create it the thumbnail is missing or reuse existing otherwise
     *
     * @param $attribute
     * @param $dimension . Desired dimension of the thumbnail. For example 120 or [120, 120]
     *
     * @return mixed|null|string
     */
    public function thumbnail($attribute, $dimension)
    {

        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $value = $owner->getAttribute($attribute);

        if ((new UrlValidator())->validate($value)) {
            return $value;
        }

        if ($value && file_exists($value)) {
            return Url::to('@web/' . (new Thumbnailer(['imagePath' => $value]))->generate($dimension), true);
        }

        return null;
    }

    /**
     * Returns the qualified URI of the image
     *
     * @param $attribute
     *
     * @return mixed|null|string URI of the image
     */
    public function url($attribute)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $value = $owner->getAttribute($attribute);

        if ((new UrlValidator())->validate($value)) {
            return $value;
        }

        if ($value && file_exists($value)) {
            return Url::to('@web/' . $value, true);
        }

        if ($this->placeholder) {
            $dimension = $this->resize ?: [Placeholder::DEFAULT_SIZE, Placeholder::DEFAULT_SIZE];

            list($width, $height) = Utils::getDimension($dimension);

            return call_user_func([$this->placeholder, 'getImage'], $width, $height);
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
    public function canGetProperty($name, $checkVars = true)
    {
        return parent::canGetProperty($name, $checkVars) || $this->isSourceAttribute($name);
    }

    /**
     * @inheritdoc
     */
    private function getSourceAttribute()
    {
        return $this->imageAttribute . $this->suffix;
    }

    /**
     * @inheritdoc
     */
    private function isSourceAttribute($name)
    {
        return $name == $this->getSourceAttribute();
    }
}
