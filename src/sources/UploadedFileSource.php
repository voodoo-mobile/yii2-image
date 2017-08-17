<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 03:12
 */

namespace vr\image\sources;

use vr\image\Mediator;
use yii\web\UploadedFile;

/**
 * Class UploadedFileSource
 * @package vr\image\sources
 */
class UploadedFileSource extends ImageSource
{
    /** @var UploadedFile */
    public $uploaded = null;

    /**
     * @return Mediator
     */
    public function createMediator()
    {
        return new Mediator([
            'filename'         => $this->uploaded->tempName,
            'unlinkOnDestruct' => false,
        ]);
    }
}