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

        $this->assertEquals('MCbzrtjFVUgGFUSJdKFUKaqt3Xcgpi6Csx', $p2sh->getAddress(NetworkFactory::litecoin()));
        $this->assertEquals('LKrfsrS4SE1tajYRQCPuRcY1sMkoFf1BN3', $p2pk->getAddress(NetworkFactory::litecoin()));

        $this->assertEquals('EMrk83fMRQoNM74qDBb45TDWLxEehWXA7u', $p2sh->getAddress(NetworkFactory::viacoin()));
        $this->assertEquals('VadYXMHgmNg3PhkQxr4EaVo7LxgVZvhAdc', $p2pk->getAddress(NetworkFactory::viacoin()));

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::viacoinTestnet()));
        $this->assertEquals('t7ZKfRypXUd7ByZGLLi5jX3AbD7KQvDj4a', $p2pk->getAddress(NetworkFactory::viacoinTestnet()));

        $this->assertEquals('9w97HrPBcRhjMLXswZvYk5DrRQQGvT2UeH', $p2sh->getAddress(NetworkFactory::dogecoin()));
        $this->assertEquals('D5mp9u4seyg7rw2rxeQAhMdrYH7pPs5gNu', $p2pk->getAddress(NetworkFactory::dogecoin()));

        $this->assertEquals('2Mwx4ckFK9pLBeknxCZt17tajwBEQXxNaWV', $p2sh->getAddress(NetworkFactory::dogecoinTestnet()));
        $this->assertEquals('nUpssuonax8qjuc3zU3cwmE9n9W7QXJjgW', $p2pk->getAddress(NetworkFactory::dogecoinTestnet()));

        // Dash
        $this->assertEquals('7X7VPCbTMLvUSjhMo3vdqKb8eNrccxgkJ1', $p2sh->getAddress(NetworkFactory::dash()));
        $this->assertEquals('XbKZStn8KGzRUsSr5wiq18A3VUyD7pdKXX', $p2pk->getAddress(NetworkFactory::dash()));

        // Dash testnet
        $this->assertEquals('8j8JLXVKUtK6u37csJvbHhQVXtdSmwYhAb', $p2sh->getAddress(NetworkFactory::dashTestnet()));
        $this->assertEquals('xwcZUjZH3eBd1BEJdNhuZ2Jc9GCduoV5cV', $p2pk->getAddress(NetworkFactory::dashTestnet()));
    }
}
