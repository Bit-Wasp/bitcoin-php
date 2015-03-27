<?php

namespace BitWasp\Bitcoin\Tests\Crypto\EcAdapter;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BaseEcAdapterTest extends AbstractTestCase
{
    public function testRecoverYfromX()
    {
        $ecAdapter = Bitcoin::getEcAdapter();
        $math = $ecAdapter->getMath();

        $f = file_get_contents(__DIR__.'/../../Data/publickey.compressed.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $byte = substr($test->compressed, 0, 2);
            $x    = $math->hexDec(substr($test->compressed, 2, 64));
            $realy= $math->hexDec(substr($test->uncompressed, 66, 64));
            $y    = $ecAdapter->recoverYfromX($x, $byte);
            $this->assertSame($realy, $y);
        }
    }
}