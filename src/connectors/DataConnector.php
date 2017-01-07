<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:08
 */

namespace vr\image\connectors;

use yii\base\Object;

/**
 * @property string lastError
 */
abstract class DataConnector extends Object
{
    /**
     * @var
     */
    public $category;
    /**
     * @var
     */
    protected $lastError;

    /**
     * @param $mediator
     * @param $filename
     *
     * @return mixed
     */
    abstract public function upload($mediator, $filename);

    /**
     * @param $filename
     * @param $with
     * @param bool $keepExtension
     * @return mixed
     */
    abstract public function rename($filename, $with, $keepExtension = true);

    /**
     * @param $filename
     * @return mixed
     */
    abstract public function locate($filename);

    /**
     * @param $filename
     * @return mixed
     */
    abstract public function drop($filename);

    /**
     * @param $filename
     * @return mixed
     */
    abstract public function exists($filename);

    /**
     * @param $source
     * @param $destination
     * @return mixed
     */
    abstract public function copy($source, $destination);

    /**
     * @param $filename
     * @param bool $utm
     * @return mixed
     */
    abstract public function url($filename, $utm = false);

    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }
}