<?php
namespace Scurriio\ORM\Json;

use \Attribute;
use \ReflectionProperty;
use \DateTimeZone;
use \DateTime;
use \DateTimeImmutable;
use Scurriio\ORM\Column\ForeignKey;
use Scurriio\ORM\Reference;
use Scurriio\ORM\Json\Json;
use Scurriio\Utils\DataAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Serialize{
    use DataAttribute;

    public function __construct(public ?string $jsonName=null, public ?bool $required=null)
    {
        
    }

    protected function initialize()
    {
        if(!isset($this->jsonName)){
            $this->jsonName = $this->effects->getName();
        }
    }

    public function toJson($instance){
        return static::propertyToJson($this->effects, $this->effects->getValue($instance));
    }

    public function fromJson($instance, mixed $values){
        $this->effects->setValue($instance, static::propertyFromJson($this->effects, $values));
    }

    

    public static function propertyFromJson(ReflectionProperty $property, $value){
        $type = $property->getType();
        if($type->isBuiltin()){
            return $value;
        }

        $name = $type->getName();
        
        switch($name){
            case int::class:
                return (int) $value;
            case bool::class:
                return !!$value;
            case "array":
                return json_decode($value, true);
            case DateTimeImmutable::class:
                return DateTimeImmutable::createFromInterface(static::deserializeDateTime($value));
            case DateTime::class:
                return static::deserializeDateTime($value);
            case Reference::class:
                return ForeignKey::getAttr($property)->makeReference($value);
            default:
                return Json::getFor($name)->deserialize($value);
        }
    }

    public const DateTimeFormat = 'Y-m-d\TH:i:s.v\Z';
    public static function propertyToJson(ReflectionProperty $property, $value){
        if(is_null($value)){
            return null;
        }
        $type = $property->getType();
        if($type->isBuiltin()){
            return $value;
        }
        switch($type->getName()){
            case DateTimeImmutable::class:
            case DateTime::class:
                return static::serializeDateTime($value);
            case Reference::class:
                return $value->keys;
            default:
                return Json::serializeAsArray($value);
        }
    }

    public static function serializeDateTime(DateTimeImmutable | DateTime $dateTime){
        $jsonDt = new DateTime();
        $jsonDt->setTimestamp($dateTime->getTimestamp());
        $jsonDt->setTimezone(new DateTimeZone("UTC"));
        return $jsonDt->format(static::DateTimeFormat);
    }
    
    public static function deserializeDateTime(string $dateString){
        $date = DateTime::createFromFormat(static::DateTimeFormat, $dateString, new DateTimeZone("UTC"));
        if(!$date){
            $date = new DateTime($dateString);
        }
        $date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        return $date;
    }
}



?>