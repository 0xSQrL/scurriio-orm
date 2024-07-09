<?php

namespace Scurriio\ORM\DTO;
use \Attribute;
use Scurriio\ORM\Table;
use Scurriio\Utils\DataAttribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class InverseReference{
    use DataAttribute;

    public DTO $referenceDto;
    public DTOProp $referenceProp;
    public DTOProp $invertProp;
    private DTO $container;

    public ?Many $many;

    public ?Where $where;

    public function __construct(public string $dto, public string $refProperty, public string $invertOn)
    {
        
    }

    protected function initialize()
    {
        $this->many = Many::tryGetAttr($this->effects);
        $this->where = Where::tryGetAttr($this->effects);
    }

    public function setup(DTO $container){
        $this->container = $container;
        $this->invertProp = $this->container->properties[$this->invertOn];

        $this->referenceDto = DTO::getFor($this->dto);
        $this->referenceProp = $this->referenceDto->properties[$this->refProperty];
    }

    public function getWhereParam(){
        $invertKey = $this->invertProp->effects->getName();
        return ":$invertKey";
    }

    public function getDtoQuery(){
        $select = $this->referenceDto->baseSelect();
        
        $keyProp = $this->referenceProp->getBaseSelector($select->alias);
        $invertKey = $this->getWhereParam();
        $where = " WHERE $keyProp=$invertKey";
        if($this->where){
            $additionalWhere = $this->where->toQuery($select->alias);
            $where = $where . " AND $additionalWhere";
        }

        return $select->alias->encapsulate(Table::$db->prepare($select->query.$where));
    }
}
