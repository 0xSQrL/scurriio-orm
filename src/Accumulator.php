<?php
namespace Scurriio\ORM;

use \PDOStatement;

/**
 * @template T
 */
abstract class Accumulator{

    protected PDOStatement $statement;

    /**
     * @return T|null
     */
    public abstract function next(): ?object;

    /**
     * @return array<T>
     */
    public function collect(): array{
        $result = [];
        while($classObj = $this->next()){
            array_push($result, $classObj);
        }
        
        return $result;
    }

}


?>