<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Scurriio\ORM\Json\BaseJson;
use Scurriio\ORM\Json\Exceptions\RequiredFieldException;
use Scurriio\ORM\Json\Json;
use Scurriio\ORM\Json\Serialize;

#[Json()]
class SimpleSerialize{
    use BaseJson;

    #[Serialize]
    public int $number;

    #[Serialize]
    public string $string;

    #[Serialize]
    public bool $truthy;


}


#[Json()]
class RequiredSerialize{
    use BaseJson;

    #[Serialize(required: true)]
    public int $number;

    #[Serialize]
    public string $string;

}

#[Json]
class NestedSerialize{
    use BaseJson;

    #[Serialize]
    public SimpleSerialize $child;
}

class SerializeTest extends \PHPUnit\Framework\TestCase{

    public function testSerialization(){
        $serial = new SimpleSerialize();

        $serial->number = 25;
        $serial->string = "This is a test";
        $serial->truthy = false;

        $stringified = Json::serialize($serial);

        $this->assertIsString($stringified);

        $destringified = SimpleSerialize::deserialize($stringified);

        $this->assertNotSame($serial, $destringified);
        $this->assertSame($serial->number, $destringified->number);
        $this->assertSame($serial->string, $destringified->string);
        $this->assertSame($serial->truthy, $destringified->truthy);
    }

    
    public function testNestedSerialization(){
        $parent = new NestedSerialize();
        $serial = new SimpleSerialize();

        $serial->number = 25;
        $serial->string = "This is a test";
        $serial->truthy = false;

        $parent->child = $serial;

        $stringified = Json::serialize($parent);

        $this->assertIsString($stringified);

        $destringified = NestedSerialize::deserialize($stringified);

        $this->assertNotSame($parent, $destringified);
        $this->assertNotSame($parent->child, $destringified->child);
        $destringified =  $destringified->child;
        $this->assertSame($serial->number, $destringified->number);
        $this->assertSame($serial->string, $destringified->string);
        $this->assertSame($serial->truthy, $destringified->truthy);
    }

    public function testRequiredThrows(){
        $this->expectException(RequiredFieldException::class);
        
        $string = '{"string": "This is a test"}';

        RequiredSerialize::deserialize($string);
    }

    public function testRequiredMissingOptional(){        
        $string = '{"number": 1}';

        $required = RequiredSerialize::deserialize($string);

        $this->assertSame(1, $required->number);
        $this->assertFalse((new ReflectionClass(RequiredSerialize::class))->getProperty('string')->isInitialized($required));
    }

    public function testTypeMismatch(){
        $this->expectException(TypeError::class);

        $string = '{"number": "Hello"}';

        SimpleSerialize::deserialize($string);
    }
}