<?php

namespace Scurriio\ORM\Alias;

/**
 * @template T
 */
class Aliased
{

    /**
     * @param T $query
     */
    public function __construct(public AliasManager $alias, public mixed $query)
    {
    }
}