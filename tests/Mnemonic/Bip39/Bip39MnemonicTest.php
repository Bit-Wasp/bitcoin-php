<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Bip39MnemonicTest extends AbstractBip39Case
{
    /**
     * @dataProvider getBip39Vectors
     * @param Bip39Mnemonic $bip39
     * @param BufferInterface $entropy
     * @param string $eMnemonic
     * @param BufferInterface $eSeed
     */
    public function testEntropyToMnemonic(Bip39Mnemonic $bip39, BufferInterface $entropy, string $eMnemonic, BufferInterface $eSeed)
    {
        $mnemonic = $bip39->entropyToMnemonic($entropy);
        $this->assertEquals($eMnemonic, $mnemonic);
    }

    /**
     * @dataProvider getBip39Vectors
     * @param Bip39Mnemonic $bip39
     * @param BufferInterface $eEntropy
     * @param string $mnemonic
     * @param BufferInterface $eSeed
     */
    public function testMnemonicToEntropy(Bip39Mnemonic $bip39, BufferInterface $eEntropy, string $mnemonic, BufferInterface $eSeed)
    {
        $entropy = $bip39->mnemonicToEntropy($mnemonic);
        $this->assertEquals($eEntropy->getBinary(), $entropy->getBinary());
    }

    public function testIncorrectWordCount()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $mnemonic = 'letter advice';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid mnemonic");
        $bip39->mnemonicToEntropy($mnemonic);
    }

    public function testFailsOnInvalidChecksum()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $mnemonic = 'jelly better achieve collect unaware mountain thought cargo oxygen act hood oxygen';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Checksum does not match");
        $bip39->mnemonicToEntropy($mnemonic);
    }

    public function testFailsOnEntropyMod4()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid entropy length");
        $bip39->entropyToMnemonic(Buffer::hex(str_repeat('00', 5)));
    }

    public function testFailsOnEntropyTooLong()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid entropy length");
        $bip39->entropyToMnemonic(Buffer::hex(str_repeat('00', 1028)));
    }

    public function testFailsOnMnemonicOfEntropyTooLong()
    {
        $bip39 = new Bip39Mnemonic(Bitcoin::getEcAdapter(), new EnglishWordList());
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about end grace oxygen maze bright face loan ticket trial leg cruel lizard bread worry reject journey perfect chef section caught neither install industry';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid mnemonic - entropy size is invalid");
        $bip39->mnemonicToEntropy($mnemonic);
    }
}
