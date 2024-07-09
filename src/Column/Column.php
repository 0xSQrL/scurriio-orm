<?php
namespace Scurriio\ORM\Column;

use \Attribute;
use \ReflectionProperty;
use \ReflectionClass;
use \PDO;
use \PDOStatement;
use \DateTime;
use \DateTimeImmutable;
use Scurriio\ORM\Reference;
use Scurriio\Utils\DataAttribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class Column{
    use DataAttribute;

    const DateTimeDBFormat = 'Y-m-d H:i:s';

    public ?Id $idData = null;
    public ?ForeignKey $fk = null;

    public function __construct(
        public ?string $dbname=null,
        public ?string $dbType=null
    )
    {
    }

    protected function initialize()
    {
        if(is_null($this->dbname)){
            $this->dbname = $this->effects->getName();
        }

        $ids = $this->effects->getAttributes(Id::class);
        if(count($ids) != 0){
            $this->idData = $ids[0]->newInstance();
        }

        $foreignKeys = $this->effects->getAttributes(ForeignKey::class);
        if(count($foreignKeys) != 0){
            $this->fk = $foreignKeys[0]->newInstance();
        }
    }

    public function isIndex(){
        return isset($this->idData);
    }

    public function isForeignKey(){
        return isset($this->fk);
    }

    public function fromDb($value){
        if($this->isForeignKey()){
            return $this->fk->makeReference(static::fromDbValue($this->fk->effectsForeign, $value));
        }
        
        return static::fromDbValue($this->effects, $value);
    }

    public function setValueFromDb($instance, $value){
        $this->effects->setValue($instance, $this->fromDb($value));
    }

    public function pdoType(){
        if($this->isForeignKey()){
            static::getPdoType($this->fk->effectsForeign);
        }
        return static::getPdoType($this->effects);
    }

    public function toDb($value){
        if($this->isForeignKey()){
            return static::toDBValue($this->fk->effectsForeign, $value->key());
        }
        return static::toDBValue($this->effects, $value);
    }

    public function applyValue(PDOStatement $statement, $value, string $param){
        $pdoType = PDO::PARAM_NULL;

        $value = $this->toDb($value);

        if(is_null($value)){
            $value = "NULL";
        }else{
            $pdoType = $this->pdoType();
        }
        $statement->bindParam($param, $value, $pdoType);
    }

    public function apply(PDOStatement $statement, object $instance, string $param){
        $value = $this->effects->getValue($instance);
        $this->applyValue($statement, $value, $param);
    }

    public static function fromDbValue(ReflectionProperty $property, $value){
        $name = $property->getType()->getName();

        if(is_null($value)){
            return null;
        }
        
        switch($name){
            case \int::class:
                return (int) $value;
            case \bool::class:
                return !!$value;
            case "array":
                return json_decode($value, true);
            case DateTimeImmutable::class:
                return new DateTimeImmutable($value);
            case DateTime::class:
                return new DateTime($value);
            default:
                return "$value";
        }
    }

    public static function getPdoType(ReflectionProperty $property): int{
        $type = $property->getType();
        switch($type->getName()){
            case \int::class:
                return PDO::PARAM_INT;
            case \bool::class:
                return PDO::PARAM_INT;
            default:
                return PDO::PARAM_STR;
        }
    }

    public static function toDBValue(ReflectionProperty $property, $value){
        if($value instanceof Reference){
            $value = $value->keys[$property->getName()];
        }
        if(is_null($value)){
            return null;
        }
        $type = $property->getType();
        switch($type->getName()){
            case DateTimeImmutable::class:
            case DateTime::class:
                return $value->format(static::DateTimeDBFormat);
            case "array":
                return json_encode($value, true);
            case \bool::class:
                return $value ? 1 : 0;
            default:
                return $value;
        }
    }
}
?>