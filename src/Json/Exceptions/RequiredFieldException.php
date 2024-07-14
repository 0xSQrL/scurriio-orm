<?php

namespace Scurriio\ORM\Json\Exceptions;

use Exception;
use Scurriio\ORM\Json\Serialize;

class RequiredFieldException extends Exception{

    public function __construct(Serialize $property)
    {
        parent::__construct("Attempted to serialize or deserialize without required field '$property->jsonName'");
    }

}
