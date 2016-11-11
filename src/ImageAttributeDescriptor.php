<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 10/11/2016
 * Time: 18:01
 */

namespace vr\image;


use vr\upload\Base64Source;
use vr\upload\ModelSource;
use vr\upload\UploadedFileSource;
use Yii;
use yii\base\Object;
use yii\db\BaseActiveRecord;
use yii\helpers\Url;
use yii\validators\UrlValidator;
use yii\web\UploadedFile;

/**
 * Class ImageAttributeDescriptor
 * @package vr\image
 */
class ImageAttributeDescriptor extends Object
{
    /**
     * @var
     */
    public $attribute;


    /**
     * @var
     */
    public $sourceAttribute;

    /**
     * @var int[] | int | null. Determines if the image needs to be resized when uploading or updating
     */
    public $resize = false;

    /**
     * @var bool. Determines whether the image need to be cropped when it is resized. False by default
     */
    public $crop = false;

    /**
     * @var \Closure | null. Placeholder factory that generates placeholder images in case of any problems with the
     *      real image
     */
    public $placeholder;

    /**
     * @var string
     */
    public $baseUrl = '@web';

    /**
     * @var string
     */
    public $writer = '\vr\upload\FileWriter';

    /**
     * @var mixed Variable that contains the content. It can be base64 or [\yii\web\UploadedFile]
     */
    public $source = null;

    /**
     *
     */
    public function init()
    {
        parent::init();

        if ($this->placeholder) {
            $this->placeholder = Yii::createObject($this->placeholder);
        }
    }

    /**
     * @inheritdoc
     */
    public function getValue($owner)
    {
        $source = null;

        if (is_string($this->source) && strlen($this->source)) {
            $source = Base64Source::create($this->source);
        } elseif ($this->source instanceof UploadedFile) {
            $source = UploadedFileSource::create($this->source);
        } elseif ($instance = UploadedFile::getInstance($owner, $this->sourceAttribute)) {
            $source = ModelSource::create($owner, $this->sourceAttribute);
        }

        if (!$source) {
            return $owner->{$this->attribute};
        }

        /** @var UploadedImage $uploaded */
        $uploaded = new UploadedImage([
            'source' => $source,
            'resize' => $this->resize
        ]);

        if ($this->resize) {
            $uploaded->resize($this->resize, $this->crop);
        }

        $writer = Yii::createObject($this->writer);

        $writer = call_user_func([$writer, 'useActiveRecord'], $owner, $this->attribute);
        $path = $uploaded->save($writer);

        (new Thumbnailer(['imagePath' => $path]))->clear();

        return $path;
    }

    /**
     * @param BaseActiveRecord $owner
     * @param bool $utm
     * @return mixed
     */
    public function url($owner, $utm = false)
    {
        $value = $owner->getAttribute($this->attribute);

        if ((new UrlValidator())->validate($value)) {
            return $value;
        }

        if ($value) {

            $baseUrl = trim($this->baseUrl, '/');
            $template = "{$baseUrl}/{$value}";

            if ($utm) {
                $template .= '?utm=' . md5(uniqid());
            }

            return Url::to($template, true);
        }

        if ($this->placeholder) {
            $dimension = $this->resize ?: [Placeholder::DEFAULT_SIZE, Placeholder::DEFAULT_SIZE];

            list($width, $height) = Utils::getDimension($dimension);

            return call_user_func([$this->placeholder, 'getImage'], $width, $height);
        }
    }

    /**
     * @param BaseActiveRecord $owner
     * @param $attribute
     * @param $dimension
     * @param bool $absoluteUrl
     * @return mixed|null|string
     */
    public function thumbnail($owner, $attribute, $dimension, $absoluteUrl = true)
    {
        $value = $owner->getAttribute($attribute);

        if ((new UrlValidator())->validate($value)) {
            return $value;
        }

        if ($value && file_exists($value)) {
            return Url::to('@web/' . (new Thumbnailer(['imagePath' => $value]))->generate($dimension), $absoluteUrl);
        }

        if ($this->placeholder) {

            list($width, $height) = Utils::getDimension($dimension);

            return call_user_func([$this->placeholder, 'getImage'], $width, $height);
        }

        return null;
    }
}