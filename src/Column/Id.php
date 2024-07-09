<?php

namespace Scurriio\ORM\Column;

use \Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Id{
    public function __construct(public bool $auto = true)
    {
        
    }
}