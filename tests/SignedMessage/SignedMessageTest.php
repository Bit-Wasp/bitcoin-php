<?php

namespace BitWasp\Bitcoin\Tests\SignedMessage;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class SignedMessageTest extends AbstractTestCase
{
    public function sampleMessage()
    {
        return
            [
                'hi',
                'n2Z2DFCxG6vktyX1MFkKAQPQFsrmniGKj5',
                '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
-----END BITCOIN SIGNED MESSAGE-----',
                NetworkFactory::bitcoinTestnet()
            ];
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testParsesMessage(EcAdapterInterface $ecAdapter)
    {
        list ($message, $address, $content, $network) = $this->sampleMessage();
        /** @var PayToPubKeyHashAddress $address */
        $address = AddressFactory::fromString($address, $network);
        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer($ecAdapter, 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface')
        );

        $signed = $serializer->parse($content);
        $signer = new MessageSigner($ecAdapter);

        $this->assertSame($message, $signed->getMessage());
        $this->assertSame('11884306385941066859834558634967777927278716082145975036347303871472774300855', $signed->getCompactSignature()->getR());
        $this->assertSame('38787429741286654786942380905403782954160859974631158035207591010286944440307', $signed->getCompactSignature()->getS());
        $this->assertEquals(1, $signed->getCompactSignature()->getRecoveryId());
        $this->assertSame(true, $signed->getCompactSignature()->isCompressed());
        $this->assertTrue($signer->verify($signed, $address));
        $this->assertSame($content, $signed->getBuffer()->getBinary());

    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Message must begin with -----BEGIN BITCOIN SIGNED MESSAGE-----
     */
    public function testInvalidMessage1()
    {
        $invalid = '-----BEGIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        -----END BITCOIN SIGNED MESSAGE-----';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer($this->safeEcAdapter(), 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface')
        );
        $serializer->parse($invalid);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Message must end with -----END BITCOIN SIGNED MESSAGE-----
     */
    public function testInvalidMessage2()
    {
        $invalid = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        ';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer($this->safeEcAdapter(), 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface')
        );
        $serializer->parse($invalid);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find start of signature
     */
    public function testInvalidMessage3()
    {
        $invalid = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi

IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        -----END BITCOIN SIGNED MESSAGE-----';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer($this->safeEcAdapter(), 'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface')
        );
        $serializer->parse($invalid);
    }
}
