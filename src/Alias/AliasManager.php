<?php

namespace Scurriio\ORM\Alias;

use Scurriio\ORM\DTO\DTOProp;
use Scurriio\Utils\RandomString;

class AliasManager
{

    public string $alias;

    /**
     * @var array<string, AliasManager>
     */
    public array $subAliases = [];

    public function __construct()
    {
        $this->alias = RandomString::make(10);
    }

    public function getSubAlias(DTOProp $prop)
    {
        $propName = $prop->effects->getName();
        if (!isset($this->subAliases[$propName])) {
            $this->subAliases[$propName] = new AliasManager();
        }

        return $this->subAliases[$propName];
    }

    /**
     * @template T
     * @param T $value
     * @return Aliased<T>
     */
    public function encapsulate($value){
        return new Aliased($this, $value);
    }
}