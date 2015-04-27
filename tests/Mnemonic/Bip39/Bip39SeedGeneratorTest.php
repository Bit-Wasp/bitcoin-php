<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;

use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Buffertools\Buffer;

class Bip39SeedGeneratorTest extends AbstractBip39Case
{
    /**
     * @dataProvider getBip39Vectors
     */
    public function testMnemonicToSeed(Bip39Mnemonic $bip39, Buffer $entropy, $mnemonic, Buffer $eSeed)
    {
        $password = 'TREZOR';
        unset($entropy);
        $seedGenerator = new Bip39SeedGenerator($bip39);
        $seed = $seedGenerator->getSeed($mnemonic, $password);
        $this->assertEquals($eSeed->getBinary(), $seed->getBinary());
    }
}
