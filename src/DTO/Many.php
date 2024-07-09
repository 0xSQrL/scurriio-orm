<?php

namespace Scurriio\ORM\DTO;
use \Attribute;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Many{
    use DataAttribute;

    public function __construct(public int $pageSize = 0)
    {
        
    }

    protected function initialize()
    {
    }
}