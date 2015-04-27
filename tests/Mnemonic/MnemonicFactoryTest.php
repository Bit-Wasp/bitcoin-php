<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic;

use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class MnemonicFactoryTest extends AbstractTestCase
{
    public function testGetElectrum()
    {
        $this->assertInstanceOf('BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumMnemonic', MnemonicFactory::electrum());
    }

    public function testGetBip39()
    {
        $this->assertInstanceOf('BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic', MnemonicFactory::bip39());
    }
}
