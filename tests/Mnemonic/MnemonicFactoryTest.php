<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Mnemonic;

use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumMnemonic;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MnemonicFactoryTest extends AbstractTestCase
{
    public function testGetElectrum()
    {
        $this->assertInstanceOf(ElectrumMnemonic::class, MnemonicFactory::electrum());
    }

    public function testGetBip39()
    {
        $this->assertInstanceOf(Bip39Mnemonic::class, MnemonicFactory::bip39());
    }
}
