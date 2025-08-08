<?php

namespace Scurriio\ORM\Json;

use Scurriio\Utils\DataAttribute;
use \Attribute;
use \ReflectionClass;
use \ReflectionObject;
use Scurriio\ORM\Json\Exceptions\RequiredFieldException;
use Scurriio\ORM\Json\Exceptions\SerializeWithoutRequiredException;

#[Attribute(Attribute::TARGET_CLASS)]
class Json{
    use DataAttribute;

    public function __construct(public bool $implicit = false, public bool $defaultRequired = false)
    {
        
    }


    /**
     * @var Serialize[]
     */
    public array $properties = [];

    protected function initialize()
    {
        $props = $this->effects->getProperties();
        foreach($props as $prop){
            
            $serialize = Serialize::tryGetAttr($prop);
            if(!isset($serialize)){
                if(!$this->implicit){
                    continue;
                }
                $serialize = new Serialize();
                Serialize::initializeFor($serialize, $prop);
            }
            array_push($this->properties, $serialize);
        }
    }

    
    
    private static array $knownTypes = [];
    public static function getFor(string $class): Json{
        if(isset(static::$knownTypes[$class])){
            return static::$knownTypes[$class];
        }

        $reflection = new ReflectionClass($class);

        $json = static::getAttr($reflection);
        static::$knownTypes[$class] = $json;
        return $json;
    }

    public static function serializeAsArray($instance){
        if(is_array($instance)){
            return array_map(fn($inner)=>static::serializeAsArray($inner), $instance);
        }

        $reflection = new ReflectionObject($instance);
        
        $json = static::getFor($reflection->getName());

        $arrayRep = []; 

        foreach($json->properties as $prop){
            if(!$prop->effects->isInitialized($instance)){
                if($prop->required || (!isset($prop->required) && $json->defaultRequired)){
                    throw new RequiredFieldException($prop);
                }
                continue;
            }
            $arrayRep[$prop->jsonName] = $prop->toJson($instance);
        }

        return $arrayRep;
    }

    public static function serialize($instance){
        return json_encode(static::serializeAsArray($instance));
    }

    public static function deserializeTo(string $class, array|string $values){
        return static::getFor($class)->deserialize($values);
    }

    
    public function deserialize(array|string $values, ?object $instance = null): object{
        if(is_string($values)){
            $values = json_decode($values, true);
        }

        if(!isset($instance)){
            /** @var object */
            $instance = $this->effects->newInstanceWithoutConstructor();
        }
        foreach($this->properties as $prop){
            if(!isset($values[$prop->jsonName])){
                if($prop->required || (!isset($prop->required) && $this->defaultRequired)){
                    throw new RequiredFieldException($prop);
                }
                continue;
            }
            $prop->fromJson($instance, $values[$prop->jsonName]);
        }

        return $instance;
    }
}