<?php

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\Messages\GetData;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class GetDataTest extends AbstractTestCase
{
    public function testGetData()
    {
        $get = new GetData([]);
        $this->assertEquals('getdata', $get->getNetworkCommand());

        $this->assertEquals(0, count($get));
        $empty = $get->getItems();
        $this->assertInternalType('array', $empty);
        $this->assertEquals(0, count($empty));

        $data1 = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $data2 = Buffer::hex('6541414141414141414141414141414141414141414141414141414141414142');
        $inv1 = new InventoryVector(InventoryVector::MSG_TX, $data1);
        $inv2 = new InventoryVector(InventoryVector::MSG_TX, $data2);
        $get = new GetData([$inv1, $inv2]);
        $this->assertEquals(2, count($get));
    }

    public function testGetDataArray()
    {
        $data1 = Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141');
        $data2 = Buffer::hex('6541414141414141414141414141414141414141414141414141414141414142');
        $array = [
            new InventoryVector(InventoryVector::MSG_TX, $data1),
            new InventoryVector(InventoryVector::MSG_TX, $data2)
        ];

        $get = new GetData($array);
        $this->assertEquals(2, count($get));
        $this->assertEquals($array[0], $get->getItem(0));
        $this->assertEquals($array[1], $get->getItem(1));
        $this->assertEquals($array, $get->getItems());

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetItemFailure()
    {
        $get = new GetData([]);
        $get->getItem(10);
    }

    public function testNetworkSerializer()
    {
        $net = Bitcoin::getDefaultNetwork();
        $parser = new NetworkMessageSerializer($net);
        $factory = new MessageFactory($net, new Random());
        $getdata = $factory->getdata([
            new InventoryVector(
                InventoryVector::MSG_BLOCK,
                Buffer::hex('4141414141414141414141414141414141414141414141414141414141414141')
            )
        ]);

        $serialized = $getdata->getNetworkMessage()->getBuffer();
        $parsed = $parser->parse($serialized)->getPayload();

        $this->assertEquals($getdata, $parsed);
    }
}
