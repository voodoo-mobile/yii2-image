<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:43
 */

namespace vr\image\connectors;

use vr\image\Mediator;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * Class FileSystemDataConnector
 * @package vr\image\connectors
 */
class FileSystemDataConnector extends DataConnector
{
    /**
     * @var string
     */
    private $uploadPath = '@webroot/uploads';

    /**
     * @var string
     */
    private $uploadUrl = '@web/uploads';

    /**
     * @param Mediator $mediator
     * @param $filename
     * @return bool
     */
    public function upload($mediator, $filename)
    {
        if (!$this->drop($filename)) {
            return false;
        }

        $absolute = $this->locate($filename);

        if (!copy($mediator->getFilename(), $absolute)) {
            $this->lastError = ArrayHelper::getValue(error_get_last(), 'message');
            return false;
        }

        return true;
    }

    /**
     * @param $filename
     * @return bool
     */
    public function drop($filename)
    {
        $absolute = $this->locate($filename);

        if (file_exists($absolute)) {
            try {
                unlink($absolute);
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();
                return false;
            }
        }

        return true;
    }

    /**
     * @param $filename
     * @return bool|string
     */
    public function locate($filename)
    {
        $directory = \Yii::getAlias($this->uploadPath) . '/' . $this->category;

        if (!file_exists($directory) && !FileHelper::createDirectory($directory)) {
            $this->lastError = ArrayHelper::getValue(error_get_last(), 'message');

            return false;
        }

        return $directory . '/' . $filename;
    }


    /**
     * @param $filename
     * @param bool $utm
     * @return string
     */
    public function url($filename, $utm = false)
    {
        $url = Url::to(\Yii::getAlias($this->uploadUrl)
            . '/' . $this->category
            . '/' . $filename);

        if ($utm) {
            $url .= '?utm=' . uniqid();
        }

        return $url;
    }

    /**
     * @param $filename
     * @param $with
     * @param bool $keepExtension
     * @return mixed
     */
    public function rename($filename, $with, $keepExtension = true)
    {
        $absolute = $this->locate($filename);

        $info = pathinfo($absolute);
        $basename = $with;

        if ($keepExtension) {
            $basename .= '.' . ArrayHelper::getValue($info, 'extension');
        }

        $path = ArrayHelper::getValue($info, 'dirname') . '/' . $basename;

        if (file_exists($absolute) && !rename($absolute, $path)) {
            $this->lastError = ArrayHelper::getValue(error_get_last(), 'message');
            return null;
        };

        return $basename;
    }
}