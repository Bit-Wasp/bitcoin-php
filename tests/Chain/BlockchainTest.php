<?php

namespace BitWasp\Bitcoin\Tests\Chain;

<<<<<<< HEAD
=======
use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockHeader;
>>>>>>> 17321d34ed4edd8c40e603e1cb873c8399d37f9a
use BitWasp\Bitcoin\Chain\Blockchain;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Chain\BlockStorage;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\UtxoSet;
use BitWasp\Buffertools\Buffer;
use Doctrine\Common\Cache\ArrayCache;

class BlockchainTest extends AbstractTestCase
{
    public function testCreate()
    {
        $math = $this->safeMath();

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

    public function testFirstBlocks()
    {
        $math = $this->safeMath();

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
                    Buffer::hex('1d00ffff'),
                    2083236893
                )
            ),
            $blocks,
            $index,
            $utxos
        );

        $blocks = $this->getBlocks();
        foreach ($blocks as $c => $blockHex) {
            $lastUtxo = $blockchain->utxos()->size();

            $block = BlockFactory::fromHex($blockHex);
            $this->assertTrue($blockchain->process($block));

            if ($c == 0) {
                continue;
            }

            $inCount = -1;
            $outCount = 0;
            foreach ($block->getTransactions()->getTransactions() as $tx) {
                $outCount += count($tx->getOutputs());
                $inCount += count($tx->getInputs());
            }

            $diff = $outCount - $inCount;
            $this->assertEquals($lastUtxo, $blockchain->utxos()->size() - $diff);
            $this->assertEquals($c, $blockchain->currentHeight());
        }
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\BlockPrevNotFound
     */
    public function testDoesntElongateChain()
    {
        $math = $this->safeMath();

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

        $cb = new Transaction();
        $cb->getInputs()->addInput(new TransactionInput(
            '0000000000000000000000000000000000000000000000000000000000000000',
            0,
            new Script()
        ));
        $cb->getOutputs()->addOutput(new TransactionOutput(
            100000000,
            new Script()
        ));

        // References wrong prevBlock
        $invalid = new Block(
            $math,
            new BlockHeader(
                1,
                '1234123412341234123412341234123412341234123412341234123412341234',
                $cb->getTransactionId(),
                time(),
                Buffer::hex('1d00ffff'),
                1
            )
        );

        $blockchain->add($invalid);
    }

    public function testProcessRandomBlock()
    {
        $math = $this->safeMath();

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

        $cb = new Transaction();
        $cb->getInputs()->addInput(new TransactionInput(
            '0000000000000000000000000000000000000000000000000000000000000000',
            0,
            new Script()
        ));
        $cb->getOutputs()->addOutput(new TransactionOutput(
            100000000,
            new Script()
        ));

        // References wrong prevBlock
        $invalid = new Block(
            $math,
            new BlockHeader(
                1,
                '1234123412341234123412341234123412341234123412341234123412341234',
                $cb->getTransactionId(),
                time(),
                Buffer::hex('1d00ffff'),
                1
            )
        );

        $this->assertFalse($blockchain->process($invalid));
    }
}
