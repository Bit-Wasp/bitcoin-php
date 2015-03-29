<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;


use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;


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
        for ($i = 0; $i < 1; $i++) {
            $priv = PrivateKeyFactory::create(false)->getHex();
            $message = Buffer::hex(Hash::sha256d($i));
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
     */
    public function testCompactSignature(EcAdapterInterface $ecAdapter, PrivateKey $private, Buffer $message)
    {
        $public = $private->getPublicKey();
        $compact = $ecAdapter->signCompact($message, $private);
        $recovered = $ecAdapter->recoverCompact($message, $compact);

        $this->assertEquals($recovered->getBuffer(), $public->getBuffer());
        $this->assertTrue($ecAdapter->verifyMessage($message, $public->getAddress(), $compact));
    }
}