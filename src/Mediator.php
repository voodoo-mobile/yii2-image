<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:12
 */

namespace vr\image;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class Mediator
 * @package vr\image
 * @property string extension
 */
class Mediator extends Object
{
    /**
     * @var string
     */
    public $filename;

    /**
     * @var bool
     */
    public $unlinkOnDestruct = true;

    /**
     * @var
     */
    public $defaultExtension;

    /**
     * @var bool
     */
    public $autoDetectExtension = true;

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        $extension = $this->defaultExtension;

        if ($this->autoDetectExtension || !$extension) {
            $mime       = FileHelper::getMimeType($this->filename);
            $extensions = FileHelper::getExtensionsByMimeType($mime);
            $extension  = ArrayHelper::getValue($extensions, max(count($extensions) - 1, 0), $this->defaultExtension);
        }

        return trim($extension);
    }

    /**
     *
     */
    function __destruct()
    {
        if ($this->unlinkOnDestruct) {
            try {
                unlink($this->filename);
            } catch (\Exception $exception) {
            }
        }
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            if ($this->canSetProperty($key)) {
                $this->$key = $value;
            }
        }
    }
}