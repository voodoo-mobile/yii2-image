<?php

namespace vr\image;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use vr\upload\UploadedFile;
use yii\base\InvalidParamException;
use yii\imagine\Image;

/** @noinspection SpellCheckingInspection */

/**
 * Class UploadedImage
 * @package vr\image
 *          Pretty same as [[UploadedFile]] but provides some addtiional methods for managing image files
 */
class UploadedImage extends UploadedFile
{
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function save($writer)
    {
        return parent::save($writer);
    }

    /**
     * Sets up the box for the uploaded image as a boundary
     *
     * @param int | int[] $dimension One or two dimensions of the box. In case of integer it will make a square box
     * @param bool        $crop      Determines if the image need to be cropped
     *
     * @return $this
     */
    public function resize($dimension, $crop = true)
    {
        $contentFile = $this->getContentFile();

        /** @var ImageInterface $imagine */
        $imagine = Image::getImagine()->open($contentFile);

        $this->performResize($imagine, $dimension, $crop);

        $imagine->save($contentFile);

        return $this;
    }

    private function getContentFile()
    {
        if (!$this->source) {
            throw new InvalidParamException('Source must be initialized before calling this method');
        }

        $contentFile = \Yii::getAlias('@runtime/' . tmpfile());
        file_put_contents($contentFile, $this->source->getContent());

        return $contentFile;
    }

    /**
     * Performs resize of the image. Imagine component is used.
     *
     * @param ImageInterface $imagine
     * @param                $resize
     * @param bool           $crop
     *
     * @return BoxInterface
     */
    private function performResize($imagine, $resize, $crop = true)
    {
        $box = $imagine->getSize();

        list($width, $height) = Utils::getDimension($resize);

        $box = $box->scale(max($width / $box->getWidth(), $height / $box->getHeight()));
        $imagine->resize($box);

        if ($crop) {

            $point = new Point(($box->getWidth() - $width) / 2,
                ($box->getHeight() - $height) / 2);

            $imagine->crop($point, new Box($width, $height));
        }

        return $box;
    }
}