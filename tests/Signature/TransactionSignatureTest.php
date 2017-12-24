<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Signature;

use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class TransactionSignatureTest extends AbstractTestCase
{

    public function testIsValid()
    {
        $s = '304402203bc90d68b698347ea1f4b51446a0725d177debe99736df2718a9bc82275a17c402200d250e0d75c1123d179d029680bd7e2a08a4917a7e3beff25b6dbdeadbe1598901';
        $this->assertTrue(TransactionSignature::isDERSignature(Buffer::hex($s)));

        $sig = TransactionSignatureFactory::fromHex($s);
        $this->assertTrue(TransactionSignature::isDERSignature($sig->getBuffer()));
        $this->assertEquals(1, $sig->getHashType());
        $this->assertSame($s, $sig->getBuffer()->getHex());
    }

    public function testSigCanonical()
    {
        $f = $this->dataFile('sig_canonical.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $sigBuf = Buffer::hex($test);
            $this->assertTrue(TransactionSignature::isDERSignature($sigBuf));
        }
    }

    public function testSigNonCanonical()
    {
        $f = $this->dataFile('sig_noncanonical.json');
        $json = json_decode($f);
        foreach ($json->test as $c => $test) {
            try {
                $sigBuf = Buffer::hex($test[1]);
                TransactionSignature::isDERSignature($sigBuf);
                throw new \Exception('Failed testing for case: ' . $test[0]);
            } catch (SignatureNotCanonical $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testSignaturesConsistent()
    {
        $f    = $this->dataFile('signatures_blockchain.json');
        $json = json_decode($f);
        foreach ($json->test as $c => $test) {
            $sig  = TransactionSignatureFactory::fromHex($test);
            $sd   = $sig->getBuffer()->getHex();
            $this->assertSame($test, $sd);
        }
    }
}
