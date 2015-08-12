<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BaseEcAdapterTest extends AbstractTestCase
{
    public function testSetAdapter()
    {
        $math = $this->safeMath();
        $g = $this->safeGenerator();
        $default = $this->safeEcAdapter();

        // Bitcoin::getEcAdapter and EcAdapterFactory::getAdapter are the same
        $defaultEc = EcAdapterFactory::getAdapter($math, $g);
        $this->assertEquals($default, $defaultEc);

        $phpecc =EcAdapterFactory::getPhpEcc($math, $g);
        $secp = EcAdapterFactory::getSecp256k1($math, $g);

        // Should check that the correct adapter was returned
        $shouldBe = (extension_loaded('secp256k1')
            ? $secp
            : $phpecc);
        $this->assertEquals($shouldBe, $defaultEc);

        // Set as Secp256k1
        $secp256k1 = EcAdapterFactory::getSecp256k1($math, $g);
        EcAdapterFactory::setAdapter($secp256k1);
        $this->assertEquals($secp256k1, EcAdapterFactory::getAdapter($math, $g));

        // Set back to the 'default'
        EcAdapterFactory::setAdapter($default);
    }


    public function testRecoverYfromX()
    {
        $phpecc = EcAdapterFactory::getPhpEcc($this->safeMath(), $this->safeGenerator());
        $math = $phpecc->getMath();

        $f = file_get_contents(__DIR__.'/../../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $byte = substr($test->compressed, 0, 2);
            $x    = $math->hexDec(substr($test->compressed, 2, 64));
            $realy= $math->hexDec(substr($test->uncompressed, 66, 64));
            $y    = $phpecc->recoverYfromX($x, $byte);
            $this->assertSame($realy, $y);
        }
    }
}
