<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKey;
use BitWasp\Buffertools\Buffer;
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

        // results in a signature of which the S needs to be 0 padded
        $vectors[] = [
            Bitcoin::getEcAdapter(),
            PrivateKeyFactory::fromHex("87c1015da1645affba1041f9d77ff5956288194604567fb9b76a9e36e8f9ab06", true),
            Buffer::hex("67050eeb5f95abf57449d92629dcf69f80c26247e207ad006a862d1e4e6498ff"),
            "1fc5f7ecee2d242911fd6eaec08b4692bc5abfa942e0854a24a556c58999a015e900c933c581752037ea92be944c10f8e7bee570c1e1d6f0cedd4e6e054a2f3b6d"
        ];

        for ($i = 0; $i < 2; $i++) {
            $priv = PrivateKeyFactory::create(false)->getHex();
            $message = Hash::sha256d(new Buffer($i));

            foreach ($this->getEcAdapters() as $adapter) {
                $vectors[] = [$adapter[0], PrivateKeyFactory::fromHex($priv, true, $adapter[0]), $message, null];
                $vectors[] = [$adapter[0], PrivateKeyFactory::fromHex($priv, false, $adapter[0]), $message, null];
            }
        }

        return $vectors;
    }

    /**
     * @dataProvider getCSVectors
     * @param EcAdapterInterface $ecAdapter
     * @param PrivateKey $private
     * @param Buffer $message
     * @param null $expectedSignature
     */
    public function testCompactSignature(EcAdapterInterface $ecAdapter, PrivateKey $private, Buffer $message, $expectedSignature = null)
    {
        $pubKey = $private->getPublicKey();
        $compact = $ecAdapter->signCompact($message, $private);

        $this->assertEquals(65, $compact->getBuffer()->getSize());

        if ($expectedSignature !== null) {
            $this->assertEquals($expectedSignature, $compact->getHex());
        }

        $recPubKey = $ecAdapter->recoverCompact($message, $compact);

        $this->assertEquals($recPubKey->getBuffer(), $pubKey->getBuffer());
        $this->assertTrue($ecAdapter->verifyMessage($message, $pubKey->getAddress(), $compact));

        $serializer = new CompactSignatureSerializer($ecAdapter->getMath());
        $parsed = $serializer->parse($compact->getBuffer());
        $this->assertEquals($compact, $parsed);
    }
}
