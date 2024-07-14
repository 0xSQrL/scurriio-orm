<?php

use Scurriio\ORM\BaseTable;
use Scurriio\ORM\Column\Column;
use Scurriio\ORM\Column\ForeignKey;
use Scurriio\ORM\Column\Id;
use Scurriio\ORM\Reference;
use Scurriio\ORM\Table;

#[Table('test_relational')]
class RelationalTestTableParent{
    use BaseTable;

    #[Column(dbType: 'INT UNSIGNED'), Id]
    public int $id;

    
    #[Column(dbType: 'VARCHAR(64)')]
    public string $description;
}


#[Table('test_relational_child')]
class RelationalTestTableChild{
    use BaseTable;

    #[Column(dbType: 'INT UNSIGNED'), Id]
    public int $id;
    
    /**
     * @var Reference<RelationalTestTableParent>
     */
    #[Column(dbType: 'INT UNSIGNED'), ForeignKey(RelationalTestTableParent::class, 'id')]
    public Reference $parent;
}

class RelationalTableTest extends \PHPUnit\Framework\TestCase{

    public static function setUpBeforeClass(): void
    {
        Table::registerGlobalDatabase(new PDO('mysql:host=localhost;dbname=scurri', 'scurri', 'scurri'));
        Table::create(RelationalTestTableParent::class);
        Table::create(RelationalTestTableChild::class);
    }

    public static function tearDownAfterClass(): void
    {
        Table::drop(RelationalTestTableChild::class);
        Table::drop(RelationalTestTableParent::class);
    }

    public function testCreateRelational(){
        $parent = new RelationalTestTableParent();
        $parent->description = "Hello";
        RelationalTestTableParent::save($parent);

        $child = new RelationalTestTableChild();
        $child->parent = $parent->toRef();

        RelationalTestTableChild::save($child);

        $loadedChild = RelationalTestTableChild::load($child->id);

        $this->assertSame($parent->id, $loadedChild->parent->key());
        $this->assertSame($parent->id, $loadedChild->parent->resolve()->id);
        $this->assertSame($parent->description, $loadedChild->parent->resolve()->description);
        $this->assertNotSame($parent, $loadedChild->parent->resolve());
    }
}

?>