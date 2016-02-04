<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use BitWasp\Buffertools\BufferInterface;

class Bip39MnemonicTest extends AbstractBip39Case
{
    /**
     * @dataProvider getBip39Vectors
     * @param Bip39Mnemonic $bip39
     * @param BufferInterface $entropy
     * @param $eMnemonic
     * @param BufferInterface $eSeed
     */
    public function testEntropyToMnemonic(Bip39Mnemonic $bip39, BufferInterface $entropy, $eMnemonic, BufferInterface $eSeed)
    {
        $mnemonic = $bip39->entropyToMnemonic($entropy);
        $this->assertEquals($eMnemonic, $mnemonic);
    }

    /**
     * @dataProvider getBip39Vectors
     * @param Bip39Mnemonic $bip39
     * @param BufferInterface $eEntropy
     * @param $mnemonic
     * @param BufferInterface $eSeed
     */
    public function testMnemonicToEntropy(Bip39Mnemonic $bip39, BufferInterface $eEntropy, $mnemonic, BufferInterface $eSeed)
    {
        $entropy = $bip39->mnemonicToEntropy($mnemonic);
        $this->assertEquals($eEntropy->getBinary(), $entropy->getBinary());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid mnemonic
     */
    public function testIncorrectWordCount()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $mnemonic = 'letter advice';
        $bip39->mnemonicToEntropy($mnemonic);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Checksum does not match
     */
    public function testFailsOnInvalidChecksum()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $mnemonic = 'jelly better achieve collect unaware mountain thought cargo oxygen act hood oxygen';
        $bip39->mnemonicToEntropy($mnemonic);
    }
}
