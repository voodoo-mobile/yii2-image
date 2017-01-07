<?php

namespace vr\image\sources;

use yii\base\Object;

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:13
 */
abstract class ImageSource extends Object
{
    abstract public function createMediator();

    public function validate() {
        // TODO: for future use
        return true;
    }
}