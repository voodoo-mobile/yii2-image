<?php
namespace vm\image;

use yii\helpers\ArrayHelper;

/**
 * Class Utils
 * @package vm\image
 *
 *          Contains the set of helpful methods for images
 */
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
    public static function getDimension($parameter)
    {
        if (is_array($parameter)) {
            $width  = ArrayHelper::getValue($parameter, 0, null);
            $height = ArrayHelper::getValue($parameter, 1, $width);

            return [$width, $height];
        }

        return [$parameter, $parameter];
    }
}