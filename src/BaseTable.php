<?php
namespace Scurriio\ORM;

trait BaseTable{

    private static Table $_table;
    public static function table(){
        if(isset(static::$_table)){
            return static::$_table;
        }
        return static::$_table = Table::getRegisteredType(static::class)->dbClass;
    }

    /**
     * @param mixed[] $keys
     * @return Reference<static>
     */
    public static function ref(mixed $keys): Reference{
        $table = static::table();
        if(is_array($keys)){
            return new Reference($table, $keys);
        }

        $pk = $table->firstKey();
        $keys = [$pk->effects->getName()=>$keys];
        return new Reference($table, $keys);
    }

    /**
     * @return Reference<static>
     */
    public function toRef(): Reference{
        $table = static::table();

        return Reference::from($table, $this);
    }

    /**
     * @return static|null
     */
    public static function load(mixed $keys){
        $table = static::table();
        if(is_array($keys)){
            return $table->load($keys);
        }

        $pk = $table->firstKey();
        $keys = [$pk->effects->getName()=>$keys];
        return $table->load($keys);
    }

    /**
     * @param static $instance
     */
    public static function save(object $instance){
        $table = static::table();
        $table->save($instance);
    }

    /**
     * @param static $instance
     */
    public static function delete(object $instance){
        $table = static::table();
        $table->delete($instance);
    }


}