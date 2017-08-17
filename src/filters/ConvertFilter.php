<?php

namespace vr\image\filters;

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:40
 */
class ConvertFilter extends Filter
{
    public $format;

    /**
     * @param \vr\image\Mediator $mediator
     *
     * @return bool
     * @throws \Exception
     */
    public function apply($mediator)
    {
        throw new \Exception('Not implemented');
    }
}