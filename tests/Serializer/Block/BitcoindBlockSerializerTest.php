<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Block;


use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use BitWasp\Bitcoin\Serializer\Block\BitcoindBlockSerializer;

class BitcoindBlockSerializerTest extends AbstractTestCase
{
    public function testGenesis()
    {
        $math = new Math();
        $bhs = new HexBlockHeaderSerializer();
        $txs = new TransactionSerializer();
        $bs = new HexBlockSerializer($math, $bhs, $txs);

        $network = NetworkFactory::bitcoin();
        $bds = new BitcoindBlockSerializer($network, $bs);

        $buffer = new Buffer($this->dataFile('genesis.dat'));
        $parser = new Parser($buffer);

        $block = $bds->fromParser($parser);

        $this->assertEquals('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f', $block->getHeader()->getBlockHash());
    }
}