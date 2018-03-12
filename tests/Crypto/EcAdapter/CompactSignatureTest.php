<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

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

        $random = new Random();
        for ($i = 0; $i < 2; $i++) {
            ;
            $message = "Message $i";

            foreach ($this->getEcAdapters() as $adapterRow) {
                $adapter = $adapterRow[0];
                $compressedFactory = PrivateKeyFactory::compressed($adapter);
                $uncompressedFactory = PrivateKeyFactory::uncompressed($adapter);

                $priv = $uncompressedFactory->generate($random)->getHex();

                $vectors[] = [$adapter, $compressedFactory->fromHex($priv), $message];
                $vectors[] = [$adapter, $uncompressedFactory->fromHex($priv), $message];
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
    public function testCompactSignature(EcAdapterInterface $ecAdapter, PrivateKeyInterface $private, string $message)
    {
        $pubKey = $private->getPublicKey();
        $msgSigner = new MessageSigner($ecAdapter);
        $signed = $msgSigner->sign($message, $private);
        $compact = $signed->getCompactSignature();

        $this->assertEquals(65, $compact->getBuffer()->getSize());
        $this->assertTrue($msgSigner->verify($signed, new PayToPubKeyHashAddress($pubKey->getPubKeyHash())));

        /** @var CompactSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter);

        $parsed = $serializer->parse($compact->getBuffer());
        $this->assertEquals($compact->getBinary(), $parsed->getBinary());
    }
}
