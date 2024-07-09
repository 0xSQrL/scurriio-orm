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
     * @return static
     */
    public static function deserialize(array | string $values){
        return static::getJson()->deserialize($values);
    }

    public function serialize(){
        return Json::serialize($this);
    }
}