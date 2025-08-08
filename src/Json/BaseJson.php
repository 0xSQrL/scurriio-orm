<?php
namespace Scurriio\ORM\Json;

trait BaseJson{
    private static Json $json;
    private static function getJson(): Json{
        if(!isset(static::$json)){
            static::$json = Json::getFor(static::class);
        }
        return static::$json;
    }

    /**
     * @param static $instance
     * @return static
     */
    public static function deserialize(array | string $values, ?object $instance = null){
        return static::getJson()->deserialize($values, $instance);
    }

    public function serialize(){
        return Json::serialize($this);
    }
}