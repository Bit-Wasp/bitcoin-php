<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;

class CompactSignatureTest extends AbstractTestCase
{
    /**
     * @return array
     */
    public function getCSVectors()
    {
        // create identical test vectors for secp256k1 and phpecc
        // Note that signatures should mean the verifying party can recover the correct pubkey, so the effects of
        // signing with a compressed/uncompressed key need to be tested (so that correct pubkey form is found, so the
        // correct address can be found)

        $vectors = [];

        $vectors[] = [
            Bitcoin::getEcAdapter(),
            PrivateKeyFactory::fromHex('87c1015da1645affba1041f9d77ff5956288194604567fb9b76a9e36e8f9ab06', true),
            hex2bin('67050eeb5f95abf57449d92629dcf69f80c26247e207ad006a862d1e4e6498ff')
        ];

        for ($i = 0; $i < 2; $i++) {
            $priv = PrivateKeyFactory::create(false)->getHex();
            $message = $i;

            foreach ($this->getEcAdapters() as $adapter) {
                $vectors[] = [$adapter[0], PrivateKeyFactory::fromHex($priv, true, $adapter[0]), $message];
                $vectors[] = [$adapter[0], PrivateKeyFactory::fromHex($priv, false, $adapter[0]), $message];
            }
        }

        return $vectors;
    }

    /**
     * @dataProvider getCSVectors
     * @param EcAdapterInterface $ecAdapter
     * @param PrivateKeyInterface $private
     * @param string $message
     */
    public function testCompactSignature(EcAdapterInterface $ecAdapter, PrivateKeyInterface $private, $message)
    {
        $pubKey = $private->getPublicKey();
        $msgSigner = new MessageSigner($ecAdapter);
        $signed = $msgSigner->sign($message, $private);
        $compact = $signed->getCompactSignature();

        $this->assertEquals(65, $compact->getBuffer()->getSize());
        $this->assertTrue($msgSigner->verify($signed, $pubKey->getAddress()));

        $serializer = EcSerializer::getSerializer($ecAdapter, 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface');
        /** @var CompactSignatureSerializerInterface $serializer */

        $parsed = $serializer->parse($compact->getBuffer());
        $this->assertEquals($compact->getBinary(), $parsed->getBinary());
    }
}
