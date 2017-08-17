<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 08/01/2017
 * Time: 02:34
 */

namespace vr\image\sources;

use vr\image\Mediator;

/**
 * Class UrlSource
 * @package vr\image\sources
 */
class UrlSource extends ImageSource
{
    /**
     * @var
     */
    public $url;

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

        $content = file_get_contents($this->url);

        file_put_contents($this->filename, $content, FILE_BINARY | LOCK_EX);

        return new Mediator([
            'filename' => $this->filename,
        ]);
    }
}