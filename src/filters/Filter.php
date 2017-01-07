<?php

namespace vr\image\filters;

use yii\base\Object;
use yii\helpers\ArrayHelper;

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:13
 */
abstract class Filter extends Object
{
    /**
     * @param \vr\image\Mediator $mediator
     * @return mixed
     */
    abstract public function apply($mediator);
}