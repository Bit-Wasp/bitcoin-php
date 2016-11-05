<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;


use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class EcdhTest extends AbstractTestCase
{
    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testCase(EcAdapterInterface $ecAdapter)
    {
        $key1 = PrivateKeyFactory::fromWif('L1evBikUgaZtRrYF7mnrghjvD9YhmX2jK7FRMQECV2p7iqDQzUEK');
        $key2 = PrivateKeyFactory::fromWif('5KKLdDaHNu8oh8E3BVwgv75uj4xDbMrLymoMFBQzT76esAxuZeU');
        $expectedSecret = Buffer::hex('40a5d6b30a03c91137e2e7b553a1bbb8852c24c330f2b54175ddeb737ff724aa');

        // Swapping private for public keys gives the same result
        $this->assertEquals($expectedSecret, $ecAdapter->ecdh($key1, $key2->getPublicKey()));
        $this->assertEquals($expectedSecret, $ecAdapter->ecdh($key2, $key1->getPublicKey()));
    }
}