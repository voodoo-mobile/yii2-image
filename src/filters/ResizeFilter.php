<?php

namespace vr\image\filters;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use vr\image\Utils;
use yii\imagine\Image;

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:40
 */
class ResizeFilter extends Filter
{
    /**
     * @var
     */
    public $dimension;

    /**
     * @var bool
     */
    public $crop = true;

    /**
     * @param \vr\image\Mediator $mediator
     * @return mixed
     */
    public function apply($mediator)
    {
        /** @var ImageInterface $imagine */
        $imagine = Image::getImagine()->open($mediator->getFilename());

        $this->performResize($imagine, $this->dimension, $this->crop);

        $imagine->save($mediator->getFilename());
    }

    /**
     * @param ImageInterface $imagine
     * @param $resize
     * @param bool $crop
     * @return mixed
     */
    private function performResize($imagine, $resize, $crop = false)
    {
        $box = $imagine->getSize();

        list($width, $height) = Utils::parseDimension($resize);

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