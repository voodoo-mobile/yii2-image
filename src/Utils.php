<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 08/01/2017
 * Time: 01:52
 */

namespace vr\image;

use yii\helpers\ArrayHelper;

class Utils
{
    /**
     * Converts one or two dimensions to width and height. For example
     * getDimension(120) returns [120, 120], getDimension([120, 120]) will return [120, 120]
     *
     * @param int | int[] $parameter
     *
     * @return array Width and height. Please use list($width, height)
     */
    public static function parseDimension($parameter)
    {
        if (is_array($parameter)) {
            $width  = ArrayHelper::getValue($parameter, 0, null);
            $height = ArrayHelper::getValue($parameter, 1, $width);

            return [$width, $height];
        }

        return [$parameter, $parameter];
    }
}