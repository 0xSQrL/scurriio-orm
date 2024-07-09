<?php

namespace Scurriio\ORM;

use Attribute;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
class BeforeSave{
    use DataAttribute;

    protected function initialize()
    {
        
    }
}