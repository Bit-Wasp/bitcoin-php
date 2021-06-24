<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\PSBT;

use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\PSBT\PSBTBip32Derivation;
use BitWasp\Buffertools\Buffer;

class PSBTBip32DerivationUnitTest extends AbstractTestCase
{
    public function testGetParams()
    {
        $rawKey = Buffer::hex("03e495306fca12c490e63353320b38d24786a68794384f0a6cea6838c976b2ce58");
        $fpr = 0xa1b2c3d4;
        $path = [0, 1, 2, 3];

        $deriv = new PSBTBip32Derivation($rawKey, $fpr, ...$path);
        $this->assertSame($rawKey, $deriv->getRawPublicKey());
        $this->assertSame($fpr, $deriv->getMasterKeyFpr());
        $this->assertSame($path, $deriv->getPath());
        $this->assertSame($rawKey->getHex(), $deriv->getPublicKey()->getHex());
    }
}
