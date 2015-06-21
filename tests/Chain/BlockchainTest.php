<?php

namespace BitWasp\Bitcoin\Tests\Chain;


use BitWasp\Bitcoin\Chain\Blockchain;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Chain\BlockStorage;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use Doctrine\Common\Cache\ArrayCache;

class BlockchainTest extends AbstractTestCase
{

    public function testCreate()
    {
        $math = new Math();

        $blocks = new BlockStorage(new ArrayCache());
        $utxos = new UtxoSet(new ArrayCache());
        $index = new BlockIndex(
            new BlockHashIndex(new ArrayCache()),
            new BlockHeightIndex(new ArrayCache())
        );

        $blockchain = new Blockchain(
            $math,
            new \BitWasp\Bitcoin\Block\Block(
                $math,
                new \BitWasp\Bitcoin\Block\BlockHeader(
                    '1',
                    '0000000000000000000000000000000000000000000000000000000000000000',
                    '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                    1231006505,
                    \BitWasp\Buffertools\Buffer::hex('1d00ffff'),
                    2083236893
                )
            ),
            $blocks,
            $index,
            $utxos
        );

        $this->assertEquals($index, $blockchain->index());
        $this->assertEquals($blocks, $blockchain->blocks());
        $this->assertEquals($utxos, $blockchain->utxos());
        $this->assertEquals(0, $blockchain->currentHeight());
        $this->assertEquals("1.000000000000", $blockchain->difficulty());

    }
}