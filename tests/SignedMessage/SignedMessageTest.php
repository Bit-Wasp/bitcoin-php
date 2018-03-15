<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\SignedMessage;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\MessageSigner\MessageSigner;
use BitWasp\Bitcoin\MessageSigner\SignedMessage;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

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
        list ($message, $addressString, $content, $network) = $this->sampleMessage();

        $addrCreator = new AddressCreator();
        /** @var PayToPubKeyHashAddress $address */
        $address = $addrCreator->fromString($addressString, $network);
        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter)
        );

        $signed = $serializer->parse($content);
        $signer = new MessageSigner($ecAdapter);

        $this->assertSame($message, $signed->getMessage());
        $this->assertSame('11884306385941066859834558634967777927278716082145975036347303871472774300855', gmp_strval($signed->getCompactSignature()->getR(), 10));
        $this->assertSame('38787429741286654786942380905403782954160859974631158035207591010286944440307', gmp_strval($signed->getCompactSignature()->getS(), 10));
        $this->assertEquals(1, $signed->getCompactSignature()->getRecoveryId());
        $this->assertSame(true, $signed->getCompactSignature()->isCompressed());
        $this->assertTrue($signer->verify($signed, $address));
        $this->assertSame($content, $signed->getBuffer()->getBinary());
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testInvalidMessage1(EcAdapterInterface $ecAdapter)
    {
        $invalid = '-----BEGIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        -----END BITCOIN SIGNED MESSAGE-----';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Message must begin with -----BEGIN BITCOIN SIGNED MESSAGE-----");

        $serializer->parse($invalid);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testInvalidMessage2(EcAdapterInterface $ecAdapter)
    {
        $invalid = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        ';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Message must end with -----END BITCOIN SIGNED MESSAGE-----");

        $serializer->parse($invalid);
    }

    /**
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $ecAdapter
     */
    public function testInvalidMessage3(EcAdapterInterface $ecAdapter)
    {
        $invalid = '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi

IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
        -----END BITCOIN SIGNED MESSAGE-----';

        $serializer = new SignedMessageSerializer(
            EcSerializer::getSerializer(CompactSignatureSerializerInterface::class, true, $ecAdapter)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to find start of signature");

        $serializer->parse($invalid);
    }

    public function testLitecoinFixture()
    {
        $network = NetworkFactory::litecoin();
        $addressCreator = new AddressCreator();
        $address = $addressCreator->fromString("LKueBopPJdhhniURL373SCQ3vx9evQbVSt", $network);
        $message = "hey there";

        $cpctSig = new Buffer(base64_decode("H7tlmAm+BRVYmFaNClCN096E+29GOVzy0sH0ev/AbPu4cIDD31G8BIfDghPP+G4tI3Nd0n3VWBB2t1dGtxhoGCQ="));
        /** @var CompactSignatureSerializerInterface $compactSigSerializer */
        $compactSigSerializer = EcSerializer::getSerializer(CompactSignatureSerializerInterface::class);
        $parsed = $compactSigSerializer->parse($cpctSig);
        $signedMessage = new SignedMessage($message, $parsed);

        $signer = new MessageSigner();
        $result = $signer->verify($signedMessage, $address, $network);
        $this->assertTrue($result);
    }
}
