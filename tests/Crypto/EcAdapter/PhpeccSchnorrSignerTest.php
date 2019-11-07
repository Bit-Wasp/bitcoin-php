<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\SchnorrSignatureSerializerInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class PhpeccSchnorrSignerTest extends AbstractTestCase
{
    public function getCompliantSignatureFixtures(): array
    {
        return [
            [
                /*$privKey = */ "0000000000000000000000000000000000000000000000000000000000000001",
                /*$pubKey = */ "0279BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798",
                /*$msg32 = */ "0000000000000000000000000000000000000000000000000000000000000000",
                /*$sig64 = */ "528F745793E8472C0329742A463F59E58F3A3F1A4AC09C28F6F8514D4D0322A258BD08398F82CF67B812AB2C7717CE566F877C2F8795C846146978E8F04782AE",
            ],
            [
                /*$privKey = */ "B7E151628AED2A6ABF7158809CF4F3C762E7160F38B4DA56A784D9045190CFEF",
                /*$pubKey = */ "02DFF1D77F2A671C5F36183726DB2341BE58FEAE1DA2DECED843240F7B502BA659",
                /*$msg32 = */ "243F6A8885A308D313198A2E03707344A4093822299F31D0082EFA98EC4E6C89",
                /*$sig64 = */ "667C2F778E0616E611BD0C14B8A600C5884551701A949EF0EBFD72D452D64E844160BCFC3F466ECB8FACD19ADE57D8699D74E7207D78C6AEDC3799B52A8E0598",
            ],
            [
                /*$privKey = */ "C90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B14E5C9",
                /*$pubKey = */ "03DD308AFEC5777E13121FA72B9CC1B7CC0139715309B086C960E18FD969774EB8",
                /*$msg32 = */ "5E2D58D8B3BCDF1ABADEC7829054F90DDA9805AAB56C77333024B9D0A508B75C",
                /*$sig64 = */ "2D941B38E32624BF0AC7669C0971B990994AF6F9B18426BF4F4E7EC10E6CDF386CF646C6DDAFCFA7F1993EEB2E4D66416AEAD1DDAE2F22D63CAD901412D116C6",
            ],
        ];
    }

    public function adapterCompliantFixtures(): array
    {
        $datasets = [];

        foreach ($this->getCompliantSignatureFixtures() as $vector) {
            foreach ($this->getEcAdapters() as $adapter) {
                $datasets[] = [$adapter[0], $vector[0], $vector[1], $vector[2], $vector[3]];
            }
        }

        return $datasets;
    }

    public function getVerificationFixtures(): array
    {
        return [
            [
                /*$pubKey = */ "03D69C3509BB99E412E68B0FE8544E72837DFA30746D8BE2AA65975F29D22DC7B9",
                /*$msg32 = */ "4DF3C3F68FCC83B27E9D42C90431A72499F17875C81A599B566C9889B9696703",
                /*$sig64 = */ "00000000000000000000003B78CE563F89A0ED9414F5AA28AD0D96D6795F9C63EE374AC7FAE927D334CCB190F6FB8FD27A2DDC639CCEE46D43F113A4035A2C7F",
            ],
        ];
    }

    public function adapterVerificationFixtures(): array
    {
        $datasets = [];

        foreach ($this->getVerificationFixtures() as $vector) {
            foreach ($this->getEcAdapters() as $adapter) {
                $datasets[] = [$adapter[0], $vector[0], $vector[1], $vector[2]];
            }
        }

        return $datasets;
    }

    public function getNegativeVerificationFixtures(): array
    {
        return [
            [
                /*$pubKey = */ "02DFF1D77F2A671C5F36183726DB2341BE58FEAE1DA2DECED843240F7B502BA659",
                /*$msg32 = */ "243F6A8885A308D313198A2E03707344A4093822299F31D0082EFA98EC4E6C89",
                /*$sig64 = */ "2A298DACAE57395A15D0795DDBFD1DCB564DA82B0F269BC70A74F8220429BA1DFA16AEE06609280A19B67A24E1977E4697712B5FD2943914ECD5F730901B4AB7",
                /*$reason = */ "incorrect R residuosity",
            ],
            [
                /*$pubKey = */ "03FAC2114C2FBB091527EB7C64ECB11F8021CB45E8E7809D3C0938E4B8C0E5F84B",
                /*$msg32 = */ "5E2D58D8B3BCDF1ABADEC7829054F90DDA9805AAB56C77333024B9D0A508B75C",
                /*$sig64 = */ "00DA9B08172A9B6F0466A2DEFD817F2D7AB437E0D253CB5395A963866B3574BED092F9D860F1776A1F7412AD8A1EB50DACCC222BC8C0E26B2056DF2F273EFDEC",
                /*$reason = */ "negated message hash",
            ],
            [
                /*$pubKey = */ "0279BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798",
                /*$msg32 = */ "0000000000000000000000000000000000000000000000000000000000000000",
                /*$sig64 = */ "787A848E71043D280C50470E8E1532B2DD5D20EE912A45DBDD2BD1DFBF187EF68FCE5677CE7A623CB20011225797CE7A8DE1DC6CCD4F754A47DA6C600E59543C",
                /*$reason = */ "negated s value",
            ],
            [
                /*$pubKey = */ "03DFF1D77F2A671C5F36183726DB2341BE58FEAE1DA2DECED843240F7B502BA659",
                /*$msg32 = */ "243F6A8885A308D313198A2E03707344A4093822299F31D0082EFA98EC4E6C89",
                /*$sig64 = */ "2A298DACAE57395A15D0795DDBFD1DCB564DA82B0F269BC70A74F8220429BA1D1E51A22CCEC35599B8F266912281F8365FFC2D035A230434A1A64DC59F7013FD",
                /*$reason = */ "negated public key",
            ],
        ];
    }

    public function adapterNegativeVerificationFixtures(): array
    {
        $datasets = [];

        foreach ($this->getNegativeVerificationFixtures() as $vector) {
            foreach ($this->getEcAdapters() as $adapter) {
                $datasets[] = [$adapter[0], $vector[0], $vector[1], $vector[2]];
            }
        }

        return $datasets;
    }
    /**
     * @dataProvider adapterCompliantFixtures
     * @param string $privKey
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testSignatureFixtures(EcAdapterInterface $ecAdapter, string $privKey, string $pubKey, string $msg32, string $sig64)
    {
        $privFactory = new PrivateKeyFactory($ecAdapter);
        $priv = $privFactory->fromHexCompressed($privKey);
        $pub = $priv->getPublicKey();
        $msg = Buffer::hex($msg32);
        $signature = $priv->signSchnorr($msg);
        $xonlyPub = $pub->asXOnlyPublicKey();
        $this->assertEquals(strtolower($sig64), $signature->getHex());
        $this->assertTrue($xonlyPub->verifySchnorr($msg, $signature));
    }

    /**
     * @dataProvider adapterVerificationFixtures
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testPositiveVerification(EcAdapterInterface $ecAdapter, string $pubKey, string $msg32, string $sig64)
    {
        //$ecAdapter = EcAdapterFactory::getPhpEcc(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $pub = $pubKeyFactory->fromHex($pubKey);
        $msg = Buffer::hex($msg32);
        /** @var SchnorrSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(SchnorrSignatureSerializerInterface::class, true, $ecAdapter);
        $sigBuf = Buffer::hex($sig64);
        $signature = $serializer->parse($sigBuf);
        $this->assertTrue($pub->asXOnlyPublicKey()->verifySchnorr($msg, $signature));
    }

    /**
     * @dataProvider adapterNegativeVerificationFixtures
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testNegativeVerification(EcAdapterInterface $ecAdapter, string $pubKey, string $msg32, string $sig64)
    {
        //$ecAdapter = EcAdapterFactory::getPhpEcc(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $pub = $pubKeyFactory->fromHex($pubKey);
        $msg = Buffer::hex($msg32);

        $sigBuf = Buffer::hex($sig64);
        /** @var SchnorrSignatureSerializerInterface $serializer */
        $serializer = EcSerializer::getSerializer(SchnorrSignatureSerializerInterface::class, true, $ecAdapter);
        $signature = $serializer->parse($sigBuf);
        $this->assertFalse($pub->asXOnlyPublicKey()->verifySchnorr($msg, $signature));
    }
}
