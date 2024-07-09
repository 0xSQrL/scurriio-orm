<?php

namespace Scurriio\ORM\DTO;
use \Attribute;
use Scurriio\ORM\Alias\AliasManager;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Where{
    use DataAttribute;

    private InverseReference $baseRef;

    /**
     * @param string[] $refProperty
     */
    public function __construct(public array $refProperties, public string $queryTemplate)
    {
        
    }

    protected function initialize()
    {
    }

    public function setup(InverseReference $baseRef){
        $this->baseRef = $baseRef;
    }

    public function toQuery(AliasManager $alias){

        $refs = array_map(function(string $refProp) use ($alias){
            return $this->baseRef->referenceDto->properties[$refProp]->getBaseSelector($alias);
        }, $this->refProperties);

        return sprintf($this->queryTemplate, ...$refs);        
    }
}