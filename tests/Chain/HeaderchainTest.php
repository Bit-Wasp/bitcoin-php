<?php

namespace BitWasp\Bitcoin\Tests\Chain;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Chain\BlockHashIndex;
use BitWasp\Bitcoin\Chain\BlockHeightIndex;
use BitWasp\Bitcoin\Chain\BlockIndex;
use BitWasp\Bitcoin\Chain\Headerchain;
use BitWasp\Bitcoin\Chain\HeaderStorage;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use Doctrine\Common\Cache\ArrayCache;

class HeaderchainTest extends AbstractTestCase
{
    public function testCreate()
    {
        $math = $this->safeMath();

        $headers = new HeaderStorage(new ArrayCache());
        $index = new BlockIndex(
            new BlockHashIndex(new ArrayCache()),
            new BlockHeightIndex(new ArrayCache())
        );

        $blockchain = new Headerchain(
            $math,
            new BlockHeader(
                '1',
                '0000000000000000000000000000000000000000000000000000000000000000',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                1231006505,
                Buffer::hex('1d00ffff'),
                2083236893
            ),
            $headers,
            $index
        );

        $this->assertEquals($index, $blockchain->index());
        $this->assertEquals($headers, $blockchain->headers());
        $this->assertEquals(0, $blockchain->currentHeight());
        $this->assertEquals("1.000000000000", $blockchain->difficulty());
    }

    public function testFirstBlocks()
    {
        $math = $this->safeMath();

        $headers = new HeaderStorage(new ArrayCache());
        $index = new BlockIndex(
            new BlockHashIndex(new ArrayCache()),
            new BlockHeightIndex(new ArrayCache())
        );

        $blockchain = new Headerchain(
            $math,
            new BlockHeader(
                '1',
                '0000000000000000000000000000000000000000000000000000000000000000',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                1231006505,
                Buffer::hex('1d00ffff'),
                2083236893
            ),
            $headers,
            $index
        );

        $blocks = $this->getBlocks();
        foreach ($blocks as $c => $blockHex) {
            $header = BlockFactory::fromHex($blockHex)->getHeader();
            $this->assertTrue($blockchain->process($header));
            if ($c == 0) {
                continue;
            }

            $this->assertEquals($c, $blockchain->currentHeight());
        }
    }


    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\BlockPrevNotFound
     */
    public function testDoesntElongateChain()
    {
        $math = $this->safeMath();

        $headers = new HeaderStorage(new ArrayCache());
        $index = new BlockIndex(
            new BlockHashIndex(new ArrayCache()),
            new BlockHeightIndex(new ArrayCache())
        );

        $headerchain = new Headerchain(
            $math,
            new BlockHeader(
                '1',
                '0000000000000000000000000000000000000000000000000000000000000000',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                1231006505,
                Buffer::hex('1d00ffff'),
                2083236893
            ),
            $headers,
            $index
        );

        // References wrong prevBlock
        $invalid = new BlockHeader(
            1,
            '1234123412341234123412341234123412341234123412341234123412341234',
            '1234123412341234123412341234123412341234123412341234123412341234',
            time(),
            Buffer::hex('1d00ffff'),
            1
        );

        $headerchain->add($invalid);
    }


    public function testProcessRandomBlock()
    {
        $math = $this->safeMath();

        $headers = new HeaderStorage(new ArrayCache());
        $index = new BlockIndex(
            new BlockHashIndex(new ArrayCache()),
            new BlockHeightIndex(new ArrayCache())
        );

        $headerchain = new Headerchain(
            $math,
            new BlockHeader(
                '1',
                '0000000000000000000000000000000000000000000000000000000000000000',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                1231006505,
                Buffer::hex('1d00ffff'),
                2083236893
            ),
            $headers,
            $index
        );

        // References wrong prevBlock
        $invalid = new BlockHeader(
            1,
            '1234123412341234123412341234123412341234123412341234123412341234',
            '1234123412341234123412341234123412341234123412341234123412341234',
            time(),
            Buffer::hex('1d00ffff'),
            1
        );

        $this->assertFalse($headerchain->process($invalid));
    }
}
