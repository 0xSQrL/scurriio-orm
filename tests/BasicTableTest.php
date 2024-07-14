<?php

use Scurriio\ORM\BaseTable;
use Scurriio\ORM\Column\Column;
use Scurriio\ORM\Column\Id;
use Scurriio\ORM\Table;

#[Table('test')]
class TestTable{
    use BaseTable;

    #[Column(dbType: 'INT UNSIGNED'), Id(false)]
    public int $id;

    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}


#[Table('test_multi')]
class TestTableMulti{
    use BaseTable;

    #[Column(dbType: 'INT UNSIGNED'), Id(false)]
    public int $id;
    
    #[Column('other_id', dbType: 'INT UNSIGNED'), Id(false)]
    public int $otherId;

    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}


#[Table('test_auto')]
class TestTableAuto{
    use BaseTable;

    #[Column(dbType: 'INT UNSIGNED'), Id]
    public int $id;

    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}

class BasicTableTest extends \PHPUnit\Framework\TestCase{

    public static function setUpBeforeClass(): void
    {
        Table::registerGlobalDatabase(new PDO('mysql:host=localhost;dbname=scurri', 'scurri', 'scurri'));
        Table::create(TestTable::class);
        Table::create(TestTableMulti::class);
        Table::create(TestTableAuto::class);

    }

    public static function tearDownAfterClass(): void
    {
        Table::drop(TestTable::class);
        Table::drop(TestTableAuto::class);
        Table::drop(TestTableMulti::class);
    }

    public function testCreateEntry(){

        $testObject = new TestTable();
        $testObject->id = 21;
        $testObject->description = "Hello";

        TestTable::save($testObject);
        
        $loaded = TestTable::load(21);

        $this->assertSame($testObject->id, $loaded->id);
        $this->assertSame($testObject->description, $loaded->description);
    }

    public function testUpdateEntry(){
        $testObject = new TestTable();
        $testObject->id = 21;
        $testObject->description = "Hello";

        TestTable::save($testObject);
        
        $loaded = TestTable::load(21);
        $loaded->description = "Changed";
        TestTable::save($loaded);

        $reloaded = TestTable::load(21);

        $this->assertSame($testObject->id, $reloaded->id);
        $this->assertNotSame($loaded, $reloaded);
        $this->assertNotSame($testObject->description, $reloaded->description);
        $this->assertSame($loaded->description, $reloaded->description);
    }

    
    public function testCreateMultiEntry(){

        $testObject = new TestTableMulti();
        $testObject->id = 21;
        $testObject->otherId = 52;
        $testObject->description = "Hello Multi";

        TestTableMulti::save($testObject);
        
        $loaded = TestTableMulti::load([
            "id"=>21,
            "otherId"=>52
        ]);
        $loaded->description = "Changed";
        TestTableMulti::save($loaded);

        
        $reloaded = TestTableMulti::load([
            "id"=>21,
            "otherId"=>52
        ]);

        $this->assertSame($testObject->id, $reloaded->id);
        $this->assertSame($testObject->otherId, $reloaded->otherId);
        $this->assertNotSame($loaded, $reloaded);
        $this->assertNotSame($testObject->description, $reloaded->description);
        $this->assertSame($loaded->description, $reloaded->description);
    }

    
    public function testCreateAutoEntry(){

        $testObject = new TestTableAuto();
        $testObject->description = "Hello Auto";

        TestTableAuto::save($testObject);

        $this->assertTrue((new ReflectionObject($testObject))->getProperty('id')->isInitialized($testObject));
        
        $loaded = TestTableAuto::load($testObject->id);

        $this->assertSame($testObject->id, $loaded->id);
        $this->assertSame($testObject->description, $loaded->description);
    }
    
    public function testUpdateAutoEntry(){

        $testObject = new TestTableAuto();
        $testObject->description = "Hello Auto";

        TestTableAuto::save($testObject);

        $loaded = TestTableAuto::load($testObject->id);
        $loaded->description = "Changed";
        TestTableAuto::save($loaded);

        $reloaded = TestTableAuto::load($testObject->id);

        $this->assertSame($testObject->id, $reloaded->id);
        $this->assertNotSame($loaded, $reloaded);
        $this->assertNotSame($testObject->description, $reloaded->description);
        $this->assertSame($loaded->description, $reloaded->description);
    }

    public function testLoadFromReference(){
        
        $testObject = new TestTableAuto();
        $testObject->description = "Hello Auto";

        TestTableAuto::save($testObject);

        $createdRef = $testObject->toRef();

        $this->assertSame($testObject, $createdRef->resolve());

        $loadRef = TestTableAuto::ref($testObject->id);

        $this->assertNotSame($testObject, $loadRef->resolve());
        $this->assertSame($testObject->id, $loadRef->resolve()->id);
        $this->assertSame($testObject->description, $loadRef->resolve()->description);
    }

    
    public function testLoadMultiFromReference(){

        $testObject = new TestTableMulti();
        $testObject->id = 21;
        $testObject->otherId = 52;
        $testObject->description = "Hello Multi";

        TestTableMulti::save($testObject);
        
        $createdRef = $testObject->toRef();

        $this->assertSame($testObject, $createdRef->resolve());

        $loadRef = TestTableMulti::ref([
            "id"=>21,
            "otherId"=>52
        ]);

        $this->assertNotSame($testObject, $loadRef->resolve());
        $this->assertSame($testObject->id, $loadRef->resolve()->id);
        $this->assertSame($testObject->otherId, $loadRef->resolve()->otherId);
        $this->assertSame($testObject->description, $loadRef->resolve()->description);
    }

}

?>