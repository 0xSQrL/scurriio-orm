<?php


namespace Scurriio\ORM\DTO;

use Scurriio\ORM\Accumulator;
use Scurriio\ORM\Alias\Aliased;

/**
 * @template T
 * @extends Accumulator<T>
 */
class DTOAccumulator extends Accumulator{

    /**
     * @param Aliased<PDOStatement> $statement
     */
    public function __construct(
        private DTO $toDto,
        private Aliased $aliased) {
            $this->statement = $aliased->query;
    }

    /**
     * @return T|null
     */
    public function next(): ?object
    {
        if(!($row = $this->statement->fetch(\PDO::FETCH_ASSOC))) return null;

        return $this->toDto->populate($row, $this->aliased->alias);
    }
}
