<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class MultisigHDTest extends AbstractTestCase
{

    public function testCreateRootWhenAlreadySorted()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));

        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $keys, $sequences, true);

        $this->assertEquals('m', $hd->getPath(), "confirm path set via constructor");
        $this->assertEquals($keys, $hd->getKeys(), "confirm keys has same order");
    }

    public function testSortedKeysAsSideEffect()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));

        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $keys, $sequences, true);

        $this->assertNotEquals($keys, $hd->getKeys(), "these cases should not match input since they will be sorted");
    }

    public function testNoSideEffectWhenNotSorting()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));

        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $keys, $sequences, false);

        $this->assertEquals($keys, $hd->getKeys(), "keys should match input when not sorting");
    }

    public function testGetRedeemScript()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));
        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $keys, $sequences, true);
        $script = $hd->getRedeemScript();

        // note the indexes - we know these keys will be of reversed order.
        $expected = '5221' . $keys[1]->getPublicKey()->getHex() . '21' . $keys[0]->getPublicKey()->getHex() . '52ae';

        $this->assertEquals($expected, $script->getHex());
    }
}
