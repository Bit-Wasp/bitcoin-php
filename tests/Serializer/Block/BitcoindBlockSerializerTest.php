<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Serializer\Block;

use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;

class BitcoindBlockSerializerTest extends AbstractTestCase
{
    public function testGenesis()
    {
        $math = new Math();
        $bhs = new BlockHeaderSerializer();
        $txs = new TransactionSerializer();
        $bs = new BlockSerializer($math, $bhs, $txs);

        $network = NetworkFactory::bitcoin();
        $bds = new BitcoindBlockSerializer($network, $bs);

        $buffer = new Buffer($this->dataFile('genesis.dat'));
        $parser = new Parser($buffer);

        $block = $bds->fromParser($parser);

        $this->assertEquals('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $block->getHeader()->getHash()->getHex());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithInvalidNetBytes()
    {
        $math = new Math();
        $bhs = new BlockHeaderSerializer();
        $txs = new TransactionSerializer();
        $bs = new BlockSerializer($math, $bhs, $txs);

        $network = NetworkFactory::bitcoin();
        $bds = new BitcoindBlockSerializer($network, $bs);

        $buffer = new Buffer('\x00\x00\x00\x00'.substr($this->dataFile('genesis.dat'), 4));
        //echo $buffer->getHex() . "\n";
        $parser = new Parser($buffer);

        $block = $bds->fromParser($parser);

        $this->assertEquals('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $block->getHeader()->getHash()->getHex());
    }


    public function testParseSerialize()
    {
        $math = new Math();
        $bhs = new BlockHeaderSerializer();
        $txs = new TransactionSerializer();
        $bs = new BlockSerializer($math, $bhs, $txs);

        $network = NetworkFactory::bitcoin();
        $bds = new BitcoindBlockSerializer($network, $bs);

        $buffer = new Buffer($this->dataFile('genesis.dat'));
        $parser = new Parser($buffer);
        $block = $bds->fromParser($parser);

        $again = $bds->parse($buffer);
        $this->assertEquals($again, $block);

        $this->assertEquals($buffer->getBinary(), $bds->serialize($block)->getBinary());
    }
}
