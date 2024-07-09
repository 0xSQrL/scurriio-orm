<?php
namespace Scurriio\ORM\DTO;

use \Attribute;
use \ReflectionClass;
use Scurriio\ORM\Alias\AliasManager;
use Scurriio\ORM\Table;
use Scurriio\Utils\DataAttribute;

/**
 * @template T
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DTO{
    use DataAttribute;
    
    public Table $referenceTable;

    /**
     * @var DTOProp[]
     */
    public array $properties = [];

    public function __construct(public string $referenceClass)
    {
        
    }

    protected function initialize()
    {
        
    }

    /**
     * @var DTO[]
     */
    private static array $knownTypes = [];


    public static function getFor(string $class): DTO{
        if(isset(static::$knownTypes[$class])){
            return static::$knownTypes[$class];
        }

        return static::setupDto(new ReflectionClass($class));
    }

    private static function setupDto(ReflectionClass $refClass): DTO{
        $dto = DTO::getAttr($refClass);
        static::$knownTypes[$refClass->getName()] = $dto;

        $dto->referenceTable = Table::getRegisteredType($dto->referenceClass)->dbClass;

        $props = $refClass->getProperties();

        foreach($props as $prop){
            if($dtoProp = DTOProp::tryGetAttr($prop)){
                $dtoProp->setup($dto);
                $dto->properties[$prop->getName()] = $dtoProp;
            }
        }

        return $dto;
    }


    public function baseSelect(){

        $alias = new AliasManager();
        $columns = [];
        $joins = [];

        foreach($this->properties as $property){
            array_push($columns, $property->getSelector($alias));
            if(isset($property->subDto)){
                array_push($joins, $property->getJoin($alias));
            }
        }

        $columns = join(', ', $columns);
        $joins = join(' ', $joins);
        $table = $this->referenceTable->table." as $alias->alias";

        return $alias->encapsulate("SELECT $columns FROM $table $joins");
    }

    /**
     * @return T
     */
    public function populate(array $queryRow, AliasManager $alias): object{
        $instance = $this->effects->newInstanceWithoutConstructor();

        foreach($this->properties as $property){
            $property->setValue($instance, $queryRow, $alias);
        }

        return $instance;
    }
}

?>