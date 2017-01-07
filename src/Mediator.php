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
 *
 * @package vr\image
 *
 * @property string extension
 */
class Mediator extends Object
{
    /**
     * @var string
     */
    public $filename;

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
        $mime = FileHelper::getMimeType($this->filename);
        $extensions = FileHelper::getExtensionsByMimeType($mime);

        return ArrayHelper::getValue($extensions, max(count($extensions) - 1, 0));
    }

    /**
     *
     */
    function __destruct()
    {
        try {
            unlink($this->filename);
        } catch (\Exception $exception) {

        }
    }
}