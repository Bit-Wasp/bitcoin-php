<?php

namespace BitWasp\Bitcoin\Tests\Serializer\Key\PrivateKey;


use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\HexPrivateKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\PrivateKey\WifPrivateKeySerializer;
use BitWasp\Bitcoin\Tests\Mnemonic\Bip39\AbstractBip39Case;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\EccFactory;

class WifPrivateKeySerializerTest extends AbstractBip39Case
{
    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @expectedExceptionMessage Private key should be always be 32 or 33 bytes (depending on if it's compressed)
     */
    public function testSerializer()
    {
        $math = new Math();
        $generator = EccFactory::getSecgCurves()->generator256k1();
        $network = NetworkFactory::bitcoin();
        $ecAdapter = EcAdapterFactory::getAdapter($math, $generator);

        $hexSerializer = new HexPrivateKeySerializer($ecAdapter);
        $wifSerializer = new WifPrivateKeySerializer($math, $hexSerializer);

        $valid = PrivateKeyFactory::create();
        $this->assertEquals($valid, $wifSerializer->parse($wifSerializer->serialize($network, $valid)));

        $invalid = Buffer::hex('0041414141414141414141414141414141');
        $b58 = Base58::encodeCheck($invalid);
        $wifSerializer->parse($b58);
    }
}