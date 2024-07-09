<?php

namespace Scurriio\ORM\Exceptions;


class ForeignKeyDoesNotExistException extends \Exception{
    

    public function __construct()
    {
        parent::__construct("Referenced foreign key entry does not exist");
    }
}
