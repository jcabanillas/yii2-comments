<?php

namespace jcabanillas\comments\traits;

use Yii;
use jcabanillas\comments\Module;

/**
 * Class ModuleTrait
 *
 * @package jcabanillas\comments\traits
 */
trait ModuleTrait
{
    /**
     * @return Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('comment');
    }
}
