<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 21:31
 */

namespace vr\image\sources;

use vr\image\Mediator;

/**
 * Class Base64Source
 * @package vr\image\sources
 */
class Base64Source extends ImageSource
{
    const STOPPER = 'base64,';

    /**
     * @var
     */
    public $data;

    /**
     * @var
     */
    private $filename;

    /**
     * @return Mediator
     */
    public function createMediator()
    {
        $this->filename = tempnam(\Yii::getAlias('@runtime'), 'image-');

        if (($pos = strpos($this->data, self::STOPPER)) !== false) {
            $this->data = substr($this->data, $pos + strlen(self::STOPPER));
        }

        file_put_contents($this->filename, base64_decode($this->data), FILE_BINARY | LOCK_EX);

        return new Mediator([
            'filename' => $this->filename,
        ]);
    }
}