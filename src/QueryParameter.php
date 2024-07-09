<?php
namespace Scurriio\ORM;

use PDOStatement;
use Scurriio\ORM\Column\Column;
use Scurriio\Utils\RandomString;

class QueryParameter{

    public function __construct(
        public string $key,
        public Column $referenceColumn)
    {
        
    }

    public static function randomKey(string $class, string $property){
        $qp = new QueryParameter(
            ':'.RandomString::make(8), 
            Table::getRegisteredType($class)->properties[$property]
        );

        return $qp;
    }

    public function apply(PDOStatement $statement, $value){
        $this->referenceColumn->applyValue($statement, $value, $this->key);
    }

}

?>