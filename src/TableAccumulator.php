<?php

namespace Scurriio\ORM;


/**
 * @template T
 * @extends Accumulator<T>
 */
class TableAccumulator extends Accumulator{
    public function __construct(private Table $table, protected \PDOStatement $statement)
    {
    }

    /**
     * @return T|null
     */
    public function next(): ?object{
        if(!($row = $this->statement->fetch(\PDO::FETCH_ASSOC))) return null;

        $created = $this->table->effects->newInstanceWithoutConstructor();
        foreach($this->table->inner->properties as $property){
            
            $dbValue = $row[$property->dbname];
            $property->setValueFromDb($created, $dbValue);
        }

        return $created;
    }

    
}