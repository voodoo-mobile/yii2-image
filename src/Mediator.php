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
     * @var bool
     */
    public $unlinkOnDestruct = true;

    /**
     * @var
     */
    public $defaultExtension;

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

        return ArrayHelper::getValue($extensions, max(count($extensions) - 1, 0), $this->defaultExtension);
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
}