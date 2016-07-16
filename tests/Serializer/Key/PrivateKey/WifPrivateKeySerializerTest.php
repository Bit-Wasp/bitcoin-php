<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Key\PrivateKey;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Bitcoin\Tests\Mnemonic\Bip39\AbstractBip39Case;
use BitWasp\Buffertools\Buffer;

class WifPrivateKeySerializerTest extends AbstractBip39Case
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @dataProvider getEcAdapters
     * @expectedException \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @expectedExceptionMessage Private key should be always be 32 or 33 bytes (depending on if it's compressed)
     */
    public function testSerializer(EcAdapterInterface $ecAdapter)
    {
        $network = NetworkFactory::bitcoin();

        $hexSerializer = EcSerializer::getSerializer(PrivateKeySerializerInterface::class, true, $ecAdapter);
        $wifSerializer = new WifPrivateKeySerializer($ecAdapter->getMath(), $hexSerializer);

        $valid = PrivateKeyFactory::create();
        $this->assertEquals($valid, $wifSerializer->parse($wifSerializer->serialize($network, $valid), $network));

        $invalid = Buffer::hex('8041414141414141414141414141414141');
        $b58 = Base58::encodeCheck($invalid);
        $wifSerializer->parse($b58);
    }
}
