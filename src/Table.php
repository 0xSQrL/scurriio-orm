<?php
namespace Scurriio\ORM;

use \Attribute;
use \ReflectionClass;
use \PDO;
use \PDOStatement;
use \PDOException;
use ReflectionProperty;
use Scurriio\ORM\Column\Column;
use Scurriio\ORM\Exceptions\DuplicateEntryException;
use Scurriio\ORM\Exceptions\ForeignKeyDoesNotExistException;
use Scurriio\ORM\Exceptions\SqlException;
use Scurriio\Utils\DataAttribute;

class InnerTable{

    /** @var Column[]  */
    public array $properties = [];

    public bool $autoIndexed = false;

    /** @var Column[]  */
    public array $keys = [];

    /** @var Column[] */
    public array $referenceColumns = [];

    
    /** @var BeforeSave[]  */
    public array $beforeSave = [];

    public Table $dbClass;
    
}

#[Attribute(Attribute::TARGET_CLASS)]
class Table{
    use DataAttribute;
    static PDO $db;

    public InnerTable $inner;

    /** @var InnerTable[]  */
    private static array $knownTypes = [];

    public function __construct(public string $table="")
    {
        
    }

    protected function initialize()
    {
        
    }

    public static function registerGlobalDatabase(PDO $database){
        static::$db = $database;
    }

    public static function getRegisteredType(string $class){

        if(isset(static::$knownTypes[$class])){
            return static::$knownTypes[$class];
        }
        
        $refClass = new ReflectionClass($class);
        $dbClass = static::getAttr($refClass);


        $innerDbClass = new InnerTable();
        $dbClass->inner = $innerDbClass;
        $innerDbClass->dbClass = $dbClass;

        $props = $refClass->getProperties();

        foreach($props as $prop){
            $dbProp = Column::tryGetAttr($prop);
            if(!isset($dbProp)) continue;
            $innerDbClass->properties[$prop->getName()] = $dbProp;
            if($dbProp->isIndex()){
                $innerDbClass->autoIndexed = $innerDbClass->autoIndexed || $dbProp->idData->auto;
                $innerDbClass->keys[$prop->getName()] = $dbProp;
            }
            if($dbProp->isForeignKey()){
                $innerDbClass->referenceColumns[$dbProp->fk->toClass] = $dbProp;
            }
        }

        $funcs = $refClass->getMethods();
        foreach($funcs as $func){
            $dbFunc = BeforeSave::tryGetAttr($func);
            if(!isset($dbFunc)) continue;

            array_push($innerDbClass->beforeSave, $dbFunc);
        }
        
        static::$knownTypes[$class] = $innerDbClass;

        return static::$knownTypes[$class];
    }

    private PDOStatement $select;
    private function selectStatement(): PDOStatement{
        if(!isset($this->select)){
            $select = $this->baseSelect();
            $where = $this->indexWhere();
    
            $this->select = static::$db->prepare("$select WHERE $where LIMIT :limit");
        }

        return $this->select;
    }

    public function firstKey(){
        return reset($this->inner->keys);
    }

    public function join(string | Table $join, string $type = ""){
        if(is_string($join)){
            $join = static::getRegisteredType($join)->dbClass;
        }
        $joinProp = $this->inner->referenceColumns[$join->effects->getName()];

        return $this->joinOn($join, $joinProp, $type);
    }

    private static function coerceTable(string | Table $table): Table{
        if(is_string($table)){
            $table = static::getRegisteredType($table)->dbClass;
        }
        return $table;
    }
    
    private function coerceColumn(string | Column $col): Column{
        if(is_string($col)){
            $col = $this->inner->properties[$col];
        }
        return $col;
    }

    public function joinOn(string | Table $join, string | Column $onCol, string $type = "", ?string $thisAlias = null, ?string $joinAlias = null){
        $join = static::coerceTable($join);
        $onCol = $this->coerceColumn($onCol);

        if(is_null($thisAlias)){
            $thisAlias = $this->table;
        }

        $joinStr = [];
        if(!empty($type)){
            array_push($joinStr, $type);
        }
        array_push($joinStr, "JOIN");
        array_push($joinStr, $join->table);
        if(!is_null($joinAlias)){
            array_push($joinStr, "AS $joinAlias");
        }else{
            $joinAlias = $join->table;
        }
        array_push($joinStr, "ON");
        array_push($joinStr, "$thisAlias.$onCol->dbname=$joinAlias.".$onCol->fk->toDb());

        return join(' ', $joinStr);
    }

    
    public function invJoin(string | Table $join, string $type = ""){
        $join = static::coerceTable($join);
        $joinProp = $join->inner->referenceColumns[$this->effects->getName()];

        return $this->invJoinOn($join, $joinProp, $type);
    }


    
    public function invJoinOn(string | Table $join, string | Column $onCol, string $type = "", ?string $thisAlias = null, ?string $joinAlias = null){
        $join = static::coerceTable($join);
        $onCol = $join->coerceColumn($onCol);
        if(is_null($thisAlias)){
            $thisAlias = $this->table;
        }

        $joinStr = [];
        if(!empty($type)){
            array_push($joinStr, $type);
        }
        array_push($joinStr, "JOIN");
        array_push($joinStr, $join->table);
        if(!is_null($joinAlias)){
            array_push($joinStr, "AS $joinAlias");
        }else{
            $joinAlias = $join->table;
        }
        array_push($joinStr, "ON");
        array_push($joinStr, "$join->table.$onCol->dbname=$this->table.".$onCol->fk->toDb());

        return join(' ', $joinStr);
    }

    public function baseSelect(?string $namespace = null){
        $table = $this->table;

        $cols = array_map(fn(Column $prop)=>$prop->dbname, $this->inner->properties);

        if(isset($namespace)){
            $cols = array_map(fn(string $prop)=>"$namespace.$prop", $cols);
        }

        $cols = join(", ", $cols);

        return "SELECT $cols FROM $table";
    }

    /**
     * Generates a where clause for the indexes of the table
     */
    private function indexWhere(){
        return join(" AND ", array_map(function(Column $prop){
            $dbName = $prop->dbname;
            $propName = $prop->effects->getName();
            return "$dbName=:$propName";
        }, $this->inner->keys));
    }

    public function load(array $keys): ?object{
        $query = $this->selectStatement($this->inner);

        $one = 1;
        $query->bindParam(":limit", $one, PDO::PARAM_INT);

        foreach($keys as $key=>$value){
            $this->inner->keys[$key]->applyValue($query, $value, ":$key");
        }

        $query->execute();

        $TableAccumulator = new TableAccumulator($this, $query);
        
        return  $TableAccumulator->next();
    }

    private function baseInsert(): string{

        $parameters = [];
        $values = [];

        foreach($this->inner->properties as $property){
            if($property->isIndex() && $property->idData->auto){
                continue;
            }
            array_push($parameters, $property->dbname);
            array_push($values, ':'.$property->effects->getName());
        }


        $values = join(", ", $values);
        $parameters = join(", ", $parameters);

        return "INSERT INTO $this->table($parameters) VALUES ($values)";
    }

    private function updateQuerySetProps(string $append = ''): string{
        
        $parameters = [];
        foreach($this->inner->properties as $property){
            $propName = $property->effects->getName();
            if($property->isIndex()){
                continue;
            }
            array_push($parameters, "$property->dbname=:$propName$append");
        }
        return join(", ", $parameters);
    }

    private function updateQuery(): string{
        
        $parameters = $this->updateQuerySetProps();
        $keys = $this->indexWhere();

        return "UPDATE $this->table SET $parameters WHERE $keys";
    }

    private function deleteQuery(): string{
        $keys = [];

        foreach($this->inner->properties as $property){
            if($property->isIndex()){
                $propName = $property->effects->getName();
                array_push($keys, "$property->dbname=:$propName");
                continue;
            }
        }

        $keys = join(" AND ", $keys);

        return "DELETE FROM $this->table WHERE $keys";
    }

    private function bindSaveParams(PDOStatement $statement, object $instance, bool $includeAutos, bool $includeKeys=true, string $append = ''){
        foreach($this->inner->properties as $property){
            if($property->isIndex()){
                if(!$includeKeys){
                    continue;
                }
                if(!$includeAutos && $property->idData->auto){
                    continue;
                }
            }
            $property->apply($statement, $instance, ':'.$property->effects->getName().$append);
        }
    }

    private function bindKeys(PDOStatement $statement, object $instance){
        foreach($this->inner->keys as $property){
            $property->apply($statement, $instance, ':'.$property->effects->getName());
        }
    }

    private PDOStatement $insert;
    private function insertStatement() : PDOStatement{
        if(!isset($this->insert)){
            $this->insert = static::$db->prepare($this->baseInsert());
        }
        return $this->insert;
    }

    private PDOStatement $insertWithUpdate;
    private function insertWithUpdateStatement() : PDOStatement{
        if(!isset($this->insertWithUpdate)){
            $this->insertWithUpdate = static::$db->prepare($this->baseInsert() . " ON DUPLICATE KEY UPDATE " . $this->updateQuerySetProps(2));
        }
        return $this->insertWithUpdate;
    }

    private PDOStatement $update;
    private function updateStatement() : PDOStatement{
        if(!isset($this->update)){
            $this->update = static::$db->prepare($this->updateQuery());
        }
        return $this->update;
    }

    private PDOStatement $delete;
    private function deleteStatement() : PDOStatement{
        if(!isset($this->delete)){
            $this->delete = static::$db->prepare($this->deleteQuery());
        }
        return $this->delete;
    }

    /**
     * @throws ForeignKeyDoesNotExistException
     * @throws DuplicateEntryException
     * @throws SqlException
     */
    public static function tryExecute(PDOStatement $pdoQuery, bool $endTransaction = false){
        try{

            $success = $pdoQuery->execute();

            $error = $pdoQuery->errorInfo();
            if($error[1] != 0 || !$success){
                if($endTransaction){
                    static::$db->rollBack();
                }
    
                switch($error[1]){
                    case 1216:
                        throw new ForeignKeyDoesNotExistException();
                    default:
                        throw new SqlException($error);
                }
    
            }
        }catch(PDOException){
            $error = $pdoQuery->errorInfo();
            switch($error[1]){
                case 1216:
                    throw new ForeignKeyDoesNotExistException();
                case 1062:
                    throw new DuplicateEntryException();
                default:
                    throw new SqlException($error);
            }
        }
            
       
    }

    private function innerSave(object $instance){
        $keys = $this->getKeys($instance);
        if(!$keys){
            // Insert
            static::$db->beginTransaction();
            $insert = $this->insertStatement();
            $this->bindSaveParams($insert, $instance, false);
            static::tryExecute($insert, true);

            $first = $this->firstKey();
            if(!$first)
            {
                // This object doesn't have a primary key?????
                return;
            }
            $pk = $first;
            $pk->effects->setValue($instance, Column::fromDbValue($pk->effects, static::$db->lastInsertId()));
            static::$db->commit();
            return;
        }

        if(!$this->inner->autoIndexed){
            // Insert with ON DUPLICATE KEY UPDATE;
            $insert = $this->insertWithUpdateStatement();
            $this->bindSaveParams($insert, $instance, false);
            $this->bindSaveParams($insert, $instance, false, false, "2");
            static::tryExecute($insert);
            return;
        }

        // Update
        $update = $this->updateStatement();
        $this->bindSaveParams($update, $instance, true);
        static::tryExecute($update);
    }

    public function save(object $instance){
        foreach($this->inner->beforeSave as $save){
            $save->effects->getClosure($instance)();
        }
        $this->innerSave($instance);
    }

    public function delete(object $instance){
        $delete = $this->deleteStatement();
        $this->bindKeys($delete, $instance);
        $this->tryExecute($delete);
    }

    public function deleteFromKeys(array $keys){
        $delete = $this->deleteStatement();

        foreach($keys as $key=>$value){
            $pdo = Column::getPdoType($this->effects->getProperty($key));
            $delete->bindParam(":$key", $value, $pdo);
        }
        $this->tryExecute($delete);
    }

    public function getKeys(object $instance){
        $keys = [];
        foreach($this->inner->keys as $key){
            if(!$key->effects->isInitialized($instance)){
                return false;
            }
            $keys[$key->effects->getName()] = $key->effects->getValue($instance);
        }        

        return $keys;
    }

    public static function loadObject(string $class, array $keys): object{
        $innerDbClass = static::getRegisteredType($class);

        return $innerDbClass->dbClass->load($keys);
    }

    public static function create(string | Table $table){
        $table = static::coerceTable($table);

        $tableName = $table->table;


        $columnDefinitions = [];

        foreach($table->inner->properties as $column){
            if($column->isIndex()){
                continue;
            }
            $null = $column->effects->getType()->allowsNull() ? "DEFAULT NULL" : "NOT NULL";
            array_push($columnDefinitions, "$column->dbname $column->dbType $null");
        }

        $pkDefinition = [];
        foreach($table->inner->keys as $keyColumn){
            $colData = [$keyColumn->dbname, $keyColumn->dbType, 'NOT NULL'];
            if($keyColumn->idData->auto){
                array_push($colData, "AUTO_INCREMENT");
            }
            array_push($columnDefinitions, join(' ', $colData));
            array_push($pkDefinition, $keyColumn->dbname);
        }

        $fkDefinitions = [];
        foreach($table->inner->referenceColumns as $refColumn){
            $ref = $refColumn->fk;
            $toCol = $ref->toDb();
            $toTable = $ref->getTable()->table;
            array_push($fkDefinitions, "FOREIGN KEY ($refColumn->dbname) REFERENCES $toTable($toCol)");
        }

        if(count($fkDefinitions) > 0){
            $columnDefinitions = array_merge($columnDefinitions, $fkDefinitions);
        }
        $columns = join(", ", $columnDefinitions);
        $pk = join(", ", $pkDefinition);
        $createTableStatement = static::$db->prepare("CREATE TABLE $tableName ($columns, PRIMARY KEY ($pk))");
        static::tryExecute($createTableStatement);
    }

    public static function drop(string | Table $table){
        $table = static::coerceTable($table);
        $tableName = $table->table;
        
        $createTableStatement = static::$db->prepare("DROP TABLE $tableName");
        static::tryExecute($createTableStatement);
    }
}

?>