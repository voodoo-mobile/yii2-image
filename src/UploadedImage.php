<?php

namespace vr\image;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use vr\upload\UploadedFile;
use yii\imagine\Image;

/** @noinspection SpellCheckingInspection */

/**
 * Class UploadedImage
 * @package vr\image
 *
 *          Pretty same as [[UploadedFile]] but provides some addtiional methods for managing image files
 */
class UploadedImage extends UploadedFile
{
    /**
     * @var int[] | null Size of the box that will be used for resizing an image. The image will be reduced or enlarged
     *      proportionally to fill the box
     */
    public $resize;

    /**
     * @var bool Identifies if the image need to be cropped by the provided size
     */
    public $crop = true;

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
        $this->resize = $dimension;
        $this->crop   = $crop;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function save($writer)
    {
        $filename = parent::save($writer);

        /** @var ImageInterface $imagine */
        $imagine = Image::getImagine()->open($filename);

        if ($this->resize) {
            $this->performResize($imagine);
        }

        $imagine->save($filename);

        return $filename;
    }

    /**
     * Performs resize of the image. Imagine component is used.
     *
     * @param ImageInterface $imagine
     *
     * @return BoxInterface
     *
     */
    private function performResize($imagine)
    {
        $box = $imagine->getSize();

        list($width, $height) = Utils::getDimension($this->resize);

        $box = $box->scale(max($width / $box->getWidth(), $height / $box->getHeight()));
        $imagine->resize($box);

        if ($this->crop) {

            $point = new Point(($box->getWidth() - $width) / 2,
                ($box->getHeight() - $height) / 2);

            $imagine->crop($point, new Box($width, $height));
        }

        return $box;
    }
}