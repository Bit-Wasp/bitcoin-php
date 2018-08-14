<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\SchnorrSigner;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\Signature;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use Mdanter\Ecc\EccFactory;

class PhpeccSchnorrSignerTest extends AbstractTestCase
{
    public function getCompliantSignatureFixtures(): array
    {
        return [
            [
                /*$privKey = */ "0000000000000000000000000000000000000000000000000000000000000001",
                /*$pubKey = */ "0279BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798",
                /*$msg32 = */ "0000000000000000000000000000000000000000000000000000000000000000",
                /*$sig64 = */ "787A848E71043D280C50470E8E1532B2DD5D20EE912A45DBDD2BD1DFBF187EF67031A98831859DC34DFFEEDDA86831842CCD0079E1F92AF177F7F22CC1DCED05",
            ],
            [
                /*$privKey = */ "B7E151628AED2A6ABF7158809CF4F3C762E7160F38B4DA56A784D9045190CFEF",
                /*$pubKey = */ "02DFF1D77F2A671C5F36183726DB2341BE58FEAE1DA2DECED843240F7B502BA659",
                /*$msg32 = */ "243F6A8885A308D313198A2E03707344A4093822299F31D0082EFA98EC4E6C89",
                /*$sig64 = */ "2A298DACAE57395A15D0795DDBFD1DCB564DA82B0F269BC70A74F8220429BA1D1E51A22CCEC35599B8F266912281F8365FFC2D035A230434A1A64DC59F7013FD",
            ],
            [
                /*$privKey = */ "C90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B14E5C7",
                /*$pubKey = */ "03FAC2114C2FBB091527EB7C64ECB11F8021CB45E8E7809D3C0938E4B8C0E5F84B",
                /*$msg32 = */ "5E2D58D8B3BCDF1ABADEC7829054F90DDA9805AAB56C77333024B9D0A508B75C",
                /*$sig64 = */ "00DA9B08172A9B6F0466A2DEFD817F2D7AB437E0D253CB5395A963866B3574BE00880371D01766935B92D2AB4CD5C8A2A5837EC57FED7660773A05F0DE142380",
            ],
        ];
    }

    /**
     * @dataProvider getCompliantSignatureFixtures
     * @param string $privKey
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testSignatureFixtures(string $privKey, string $pubKey, string $msg32, string $sig64)
    {
        $ecAdapter = EcAdapterFactory::getPhpEcc(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $privFactory = new PrivateKeyFactory($ecAdapter);
        $priv = $privFactory->fromHexCompressed($privKey);
        $pub = $priv->getPublicKey();
        $msg = Buffer::hex($msg32);
        $schnorrSigner = new SchnorrSigner($ecAdapter);
        $signature = $schnorrSigner->sign($priv, $msg);

        $math = $ecAdapter->getMath();
        $r = $math->intToFixedSizeString($signature->getR(), 32);
        $s = $math->intToFixedSizeString($signature->getS(), 32);
        $this->assertEquals(strtolower($sig64), bin2hex($r.$s));
        $this->assertTrue($schnorrSigner->verify($msg, $pub, $signature));
    }

    public function getVerificationFixtures(): array
    {
        return [
            [
                /*$pubKey = */ "03DEFDEA4CDB677750A420FEE807EACF21EB9898AE79B9768766E4FAA04A2D4A34",
                /*$msg32 = */ "4DF3C3F68FCC83B27E9D42C90431A72499F17875C81A599B566C9889B9696703",
                /*$sig64 = */ "00000000000000000000003B78CE563F89A0ED9414F5AA28AD0D96D6795F9C6302A8DC32E64E86A333F20EF56EAC9BA30B7246D6D25E22ADB8C6BE1AEB08D49D",
            ],
        ];
    }

    /**
     * @dataProvider getVerificationFixtures
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testPositiveVerification(string $pubKey, string $msg32, string $sig64)
    {
        $ecAdapter = EcAdapterFactory::getPhpEcc(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $pub = $pubKeyFactory->fromHex($pubKey);
        $msg = Buffer::hex($msg32);
        $schnorrSigner = new SchnorrSigner($ecAdapter);
        $sigBuf = Buffer::hex($sig64);
        $r = $sigBuf->slice(0, 32)->getGmp();
        $s= $sigBuf->slice(32, 64)->getGmp();
        $signature = new Signature($ecAdapter, $r, $s);
        $this->assertTrue($schnorrSigner->verify($msg, $pub, $signature));
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

    /**
     * @dataProvider getNegativeVerificationFixtures
     * @param string $pubKey
     * @param string $msg32
     * @param string $sig64
     * @throws \Exception
     */
    public function testNegativeVerification(string $pubKey, string $msg32, string $sig64)
    {
        $ecAdapter = EcAdapterFactory::getPhpEcc(new Math(), EccFactory::getSecgCurves()->generator256k1());
        $pubKeyFactory = new PublicKeyFactory($ecAdapter);
        $pub = $pubKeyFactory->fromHex($pubKey);
        $msg = Buffer::hex($msg32);
        $schnorrSigner = new SchnorrSigner($ecAdapter);
        $sigBuf = Buffer::hex($sig64);
        $r = $sigBuf->slice(0, 32)->getGmp();
        $s= $sigBuf->slice(32, 64)->getGmp();
        $signature = new Signature($ecAdapter, $r, $s);
        $this->assertFalse($schnorrSigner->verify($msg, $pub, $signature));
    }
}
