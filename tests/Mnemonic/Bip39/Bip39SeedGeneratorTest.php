<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;

use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Buffertools\BufferInterface;

class Bip39SeedGeneratorTest extends AbstractBip39Case
{
    /**
     * @param Bip39Mnemonic $bip39
     * @param BufferInterface $entropy
     * @param $mnemonic
     * @param BufferInterface $eSeed
     * @dataProvider getBip39Vectors
     */
    public function testMnemonicToSeed(Bip39Mnemonic $bip39, BufferInterface $entropy, $mnemonic, BufferInterface $eSeed)
    {
        $password = 'TREZOR';
        $seedGenerator = new Bip39SeedGenerator($bip39);
        $seed = $seedGenerator->getSeed($mnemonic, $password);
        $this->assertEquals($eSeed->getBinary(), $seed->getBinary());
    }
}
