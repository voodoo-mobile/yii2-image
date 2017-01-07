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

    /**
     * Converts one or two dimensions to width and height. For example
     * getDimension(120) returns [120, 120], getDimension([120, 120]) will return [120, 120]
     *
     * @param int | int[] $parameter
     *
     * @return array Width and height. Please use list($width, height)
     */
    public function getDimensions($parameter)
    {
        if (is_array($parameter)) {
            $width = ArrayHelper::getValue($parameter, 0, null);
            $height = ArrayHelper::getValue($parameter, 1, $width);

            return [$width, $height];
        }

        return [$parameter, $parameter];
    }
}