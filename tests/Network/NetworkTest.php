<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Network;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class NetworkTest extends AbstractTestCase
{
    public function testFactoryPresets()
    {
        $p2sh = new ScriptHashAddress(Buffer::hex('3399bc19f2b20473d417e31472c92947b59f95f8'));
        $p2pk = new PayToPubKeyHashAddress(Buffer::hex('06f1b66ffe49df7fce684df16c62f59dc9adbd3f'));

        $this->assertEquals('36PrZ1KHYMpqSyAQXSG8VwbUiq2EogxLo2', $p2sh->getAddress(NetworkFactory::bitcoin()));
        $this->assertEquals('1dice8EMZmqKvrGE4Qc9bUFf9PX3xaYDp', $p2pk->getAddress(NetworkFactory::bitcoin()));

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::bitcoinTestnet()));
        $this->assertEquals('mg9fuhDDAbD673KswdNyyWgaX8zDxJT8QY', $p2pk->getAddress(NetworkFactory::bitcoinTestnet()));

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::bitcoinRegtest()));
        $this->assertEquals('mg9fuhDDAbD673KswdNyyWgaX8zDxJT8QY', $p2pk->getAddress(NetworkFactory::bitcoinRegtest()));
    }
}
