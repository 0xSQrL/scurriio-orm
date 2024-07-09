<?php

namespace Scurriio\ORM\Exceptions;

class DuplicateEntryException extends \Exception{

    public function __construct()
    {
        parent::__construct("Attempted to create entry with duplicate primary key");
    }
}