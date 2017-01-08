<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:08
 */

namespace vr\image\connectors;

use vr\image\Mediator;
use yii\base\Object;

/**
 * @property string lastError
 */
abstract class DataConnector extends Object
{
    /**
     * @var
     */
    public $folder;

    /**
     * @var
     */
    protected $lastError;

    /**
     * @param Mediator $mediator
     * @param string $filename
     *
     * @return bool
     */
    abstract public function upload($mediator, $filename);

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    abstract public function rename($source, $destination);

    /**
     * @param string $filename
     * @return string
     */
    abstract public function locate($filename);

    /**
     * @param string $filename
     * @return bool
     */
    abstract public function drop($filename);

    /**
     * @param string $filename
     * @return bool
     */
    abstract public function cleanUp($filename);

    /**
     * @param string $filename
     * @return bool
     */
    abstract public function exists($filename);

    /**
     * @param string $filename
     * @param bool $utm
     * @return string
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