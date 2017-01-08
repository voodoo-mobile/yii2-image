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

        return $this->cleanUp($filename);
    }

    /**
     * @param $filename
     * @return bool|string
     */
    public function locate($filename)
    {
        $directory = \Yii::getAlias($this->uploadPath) . '/' . $this->folder;

        if (!file_exists($directory) && !FileHelper::createDirectory($directory)) {
            $this->lastError = ArrayHelper::getValue(error_get_last(), 'message');

            return false;
        }

        return $directory . '/' . $filename;
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function cleanUp($filename)
    {
        $directory = \Yii::getAlias($this->uploadPath) . '/' . $this->folder;
        $mask = pathinfo($filename, PATHINFO_FILENAME);

        foreach (FileHelper::findFiles($directory, ['only' => ["{$mask}-*x*"]]) as $file) {
            try {
                unlink($file);
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();
                return false;
            }
        }

        return true;
    }

    /**
     * @param $filename
     * @param bool $utm
     * @return string
     */
    public function url($filename, $utm = false)
    {
        if (!$filename) {
            return null;
        }

        $url = Url::to(\Yii::getAlias($this->uploadUrl)
            . '/' . $this->folder
            . '/' . $filename, true);

        if ($utm) {
            $url .= '?utm=' . uniqid();
        }

        return $url;
    }

    /**
     * @param $filename
     * @return bool
     */
    public function exists($filename)
    {
        return file_exists($this->locate($filename));
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function rename($source, $destination)
    {
        $source = $this->locate($source);
        $destination = $this->locate($destination);

        if (file_exists($source) && !rename($source, $destination)) {
            $this->lastError = ArrayHelper::getValue(error_get_last(), 'message');
            return false;
        };


        return $this->cleanUp($source);
    }
}