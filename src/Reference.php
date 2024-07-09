<?php
namespace Scurriio\ORM;

/**
 * A lazy loading reference to a database entry
 * @template T
 */
class Reference{

    public function __construct(private Table $refTo, public array $keys)
    { }

    /**
     * @var T
     */
    private ?object $value = null;

    /**
     * @return T|null
     */
    public function resolve(): ?object{
        if(is_null($this->value)){
            $this->value = $this->refTo->load($this->keys); 
        }
        return $this->value;
    }

    public function delete(){
        $this->refTo->deleteFromKeys($this->keys);
        $this->value = null;
    }

    public function key(): mixed{
        return reset($this->keys);
    }

    /**
     * Create a reference from an existing entity
     * @var T $instance
     * @return Reference<T>
     */
    public static function from(Table $refTo, object $instance): Reference{
        $ref = new static($refTo, $refTo->getKeys($instance));
        $ref->value = $instance;
        return $ref;
    }

    
    /**
     * Create a reference from primary key(s)
     * @var T $instance
     * @return Reference<T>
     */
    public static function fromPrimaryKey(Table $refTo, mixed $keyValue): Reference{
        $key = $refTo->firstKey();
        $ref = new static($refTo, [$key->effects->getName()=>$keyValue]);
        return $ref;
    }
}


?>