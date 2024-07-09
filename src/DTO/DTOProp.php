<?php
namespace Scurriio\ORM\DTO;


use \Attribute;
use Exception;
use Scurriio\ORM\Alias\AliasManager;
use Scurriio\ORM\Column\Column;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DTOProp{
    use DataAttribute;

    public Column $referenceColumn;

    private DTO $container;
    public ?DTO $subDto = null;

    public function __construct(public string $referenceField)
    {
        
    }
    
    protected function initialize()
    {

    }

    public function setup(DTO $container){
        $this->container = $container;
        $this->referenceColumn = $container->referenceTable->inner->properties[$this->referenceField];

        $this->processSubDto();
    }

    private function processSubDto(): bool{
        if($this->referenceColumn->isForeignKey()){
            $type = $this->effects->getType();

            if(isset($type) && !$type->isBuiltin()){
                $this->subDto = DTO::getFor($type->getName());

                if($this->subDto->referenceTable->effects->getName() != $this->referenceColumn->fk->toClass){
                    throw new Exception("Table mismatch in DTO foreign key!");
                }

                return true;
            }
        }
        return false;
    }

    public function getAlias(AliasManager $alias){
        return $alias->alias.'_'.$this->referenceColumn->dbname;
    }

    public function getSelector(AliasManager $alias){
        if($this->subDto){
            return join(', ', $this->getSubTableSelect($alias));
        }else{
            $dbName = $this->getBaseSelector($alias);
            $aliasStr = $this->getAlias($alias);
            return "$dbName as $aliasStr";
        }
    }

    public function getBaseSelector(AliasManager $alias){
        $dbName = $this->referenceColumn->dbname;
        return "$alias->alias.$dbName";
    }

    private function getSubTableSelect(AliasManager $alias){
        $alias = $alias->getSubAlias($this);
        
        // TODO fix this as it only allows one layer of depth
        return array_map(function(DTOProp $prop) use ($alias){
            return $prop->getSelector($alias);
        }, $this->subDto->properties);
    }

    public function getJoin(AliasManager $alias){
        $subAlias = $alias->getSubAlias($this);
        return $this->container->referenceTable->joinOn($this->subDto->referenceTable, $this->referenceColumn, "LEFT", $alias->alias, $subAlias->alias);
    }

    public function setValue($instance, array $values, AliasManager $alias){
        if($this->subDto){
            $alias = $alias->getSubAlias($this);
            $this->effects->setValue($instance, $this->subDto->populate($values, $alias));
        }else{
            $this->effects->setValue($instance, $this->referenceColumn->fromDb($values[$this->getAlias($alias)]));
        }
    }

}

?>