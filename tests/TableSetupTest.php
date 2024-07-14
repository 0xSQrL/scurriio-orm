<?php

use Scurriio\ORM\Column\Column;
use Scurriio\ORM\Column\ForeignKey;
use Scurriio\ORM\Column\Id;
use Scurriio\ORM\Reference;
use Scurriio\ORM\Table;

#[Table('test')]
class TestSetupTable{

    #[Column(dbType: 'INT UNSIGNED'), Id]
    public int $id;

    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}


#[Table('test_relation')]
class TestSetupRelationalTable{

    #[Column(dbType: 'INT UNSIGNED'), Id]
    public int $id;

    
    #[Column(dbType: 'INT UNSIGNED'), ForeignKey(TestSetupTable::class, 'id')]
    public Reference $other;

    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}

class TableSetupTest extends \PHPUnit\Framework\TestCase{

    public static function setUpBeforeClass(): void
    {
        Table::registerGlobalDatabase(new PDO('mysql:host=localhost;dbname=scurri', 'scurri', 'scurri'));

    }

    public static function tearDownAfterClass(): void
    {
    }

    public function testCreateTestTable(){
        Table::create(TestSetupTable::class);

        Table::drop(TestSetupTable::class);

        $this->assertTrue(true);
    }

    public function testCreateRelationalTable(){
        Table::create(TestSetupTable::class);
        Table::create(TestSetupRelationalTable::class);

        Table::drop(TestSetupRelationalTable::class);
        Table::drop(TestSetupTable::class);

        $this->assertTrue(true);
    }
}

?>