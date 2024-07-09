<?php

namespace Scurriio\ORM\Exceptions;


class SqlException extends \Exception{
    
    public $code;

    public function __construct(array $sqlError)
    {
        $this->code = $sqlError[1];
        parent::__construct($sqlError[0]);
    }
}
