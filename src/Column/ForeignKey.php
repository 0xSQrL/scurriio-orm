<?php


namespace Scurriio\ORM\Column;

use \Attribute;
use Scurriio\ORM\Reference;
use Scurriio\ORM\Table;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKey{
    use DataAttribute;

    public \ReflectionProperty $effectsForeign;
    
    protected function initialize()
    {}

    public function __construct(public string $toClass, public string $toAttribute)
    {
        $this->effectsForeign = (new \ReflectionClass($toClass))->getProperty($toAttribute);
    }

    public function getTable(){
        return Table::getRegisteredType($this->toClass)->dbClass;
    }

    public function toDb(){
        return $this->getTable()->inner->properties[$this->toAttribute]->dbname;
    }

    public function makeReference($key): Reference{
        return new Reference($this->getTable(), [$this->toAttribute => $key]);
    }
}
