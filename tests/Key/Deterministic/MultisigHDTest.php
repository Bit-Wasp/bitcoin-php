<?php

namespace BitWasp\Bitcoin\Tests\Key\Deterministic;

use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Deterministic\MultisigHD;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class MultisigHDTest extends AbstractTestCase
{

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Must have at least one HierarchicalKey for Multisig HD Script
     */
    public function testAlwaysProvidesKeys()
    {
        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', [], $sequences, true);
    }

    public function testCreateRootWhenAlreadySorted()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));

        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new MultisigHD(2, 'm', $keys, $sequences, true);

        $this->assertEquals('m', $hd->getPath(), 'confirm path set via constructor');
        $this->assertEquals($keys, $hd->getKeys(), 'confirm keys has same order');
    }

    public function testSortedKeysAsSideEffect()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));

        $ec = $this->safeEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new MultisigHD(2, 'm', $keys, $sequences, true);
        
        $this->assertNotEquals($keys, $hd->getKeys(), 'these cases should not match input since they will be sorted');
    }

    public function testNoSideEffectWhenNotSorting()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));

        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new \BitWasp\Bitcoin\Key\Deterministic\MultisigHD(2, 'm', $keys, $sequences, false);

        $this->assertEquals($keys, $hd->getKeys(), 'keys should match input when not sorting');
    }

    public function testGetRedeemScript()
    {
        $keys[0] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'));
        $keys[1] = HierarchicalKeyFactory::fromEntropy(Buffer::hex('01'));
        $ec = $this->safeEcAdapter();
        $sequences = new HierarchicalKeySequence($ec->getMath());
        $hd = new MultisigHD(2, 'm', $keys, $sequences, true);
        $script = $hd->getRedeemScript();

        // note the indexes - we know these keys will be of reversed order.
        $expected = '5221' . $keys[1]->getPublicKey()->getHex() . '21' . $keys[0]->getPublicKey()->getHex() . '52ae';

        $this->assertEquals($expected, $script->getHex());
    }

    public function testDeriveChild()
    {
        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();

        $hd = new MultisigHD(
            2,
            'm',
            [
                HierarchicalKeyFactory::fromEntropy(Buffer::hex('01')),
                HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'))
            ],
            new HierarchicalKeySequence($ec->getMath()),
            true
        );

        $child = $hd->derivePath('0');
        $childKeys = $child->getKeys();

        // The public keys were SORTED. Therefore, the 0th may not have anything to do with the initial 0th key.
        $this->assertEquals('02d5514b338973151bdedf58a08cb0c912807ac9c7e026e6dc0f11abf8073be99e', $childKeys[0]->getPublicKey()->getHex());
        $this->assertEquals('0318c49f3d850f37d93314cb9b08ed3e864af991dc109da5b3e23a0ef4c518e5d2', $childKeys[1]->getPublicKey()->getHex());
        $this->assertEquals('522102d5514b338973151bdedf58a08cb0c912807ac9c7e026e6dc0f11abf8073be99e210318c49f3d850f37d93314cb9b08ed3e864af991dc109da5b3e23a0ef4c518e5d252ae', $child->getRedeemScript()->getHex());
        $address = $child->getAddress();
        $this->assertEquals('3GX7j2puUbkyMiWu3YYYEczJQ1ZPS9vdam', $address->getAddress());

    }

    public function testDerivePath()
    {
        $ec = \BitWasp\Bitcoin\Bitcoin::getEcAdapter();
        $hd = new MultisigHD(
            2,
            'm',
            [
                HierarchicalKeyFactory::fromEntropy(Buffer::hex('01')),
                HierarchicalKeyFactory::fromEntropy(Buffer::hex('02'))
            ],
            new HierarchicalKeySequence($ec->getMath()),
            true
        );

        $child = $hd->derivePath('0/2147483647h/1h/2147483647');
        $childKeys = $child->getKeys();

        // The public keys were SORTED. Therefore, the 0th may not have anything to do with the initial 0th key.
        $this->assertEquals('02a52960d39bede34b4c3583043d82fb2e781e83d8b7670ecee50973b95eab1199', $childKeys[0]->getPublicKey()->getHex());
        $this->assertEquals('03e53cb62d2d720b8827e214d5f306022696f0efe6efaad99dac79107e2b2f624b', $childKeys[1]->getPublicKey()->getHex());
        $address = $child->getAddress();
        $this->assertEquals('3MJdxK3kTy1THdE1mU66jR6ypUJqYkRqit', $address->getAddress());
    }
}
