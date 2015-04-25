<?php

namespace BitWasp\Bitcoin\Mnemonic\BIP39;


use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Mnemonic\MnemonicInterface;
use BitWasp\Buffertools\Buffer;

class Bip39Mnemonic implements MnemonicInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
        $this->wordList = new Bip39WordList();
    }

    /**
     * @param Buffer $entropy
     * @return array
     */
    public function entropyToWords(Buffer $entropy)
    {
        $math = $this->ecAdapter->getMath();

        $ENT = $entropy->getSize() * 8;
        $CS = $ENT / 32;

        $csBits = $this->calculateChecksum($entropy, $CS);
        $entBits = $math->baseConvert($entropy->getBinary(), 256, 2);
        $bits = str_pad($entBits . $csBits, ($ENT + $CS), '0', STR_PAD_LEFT);

        $result = [];
        foreach(str_split($bits, 11) as $bit) {
            $idx = $math->baseConvert($bit, 2, 10);
            $result[] = $this->wordList->getWord($idx);
        }

        return $result;
    }

    /**
     * @param Buffer $entropy
     * @param integer $CSlen
     * @return string
     */
    private function calculateChecksum(Buffer $entropy, $CSlen)
    {
        $entHash = Hash::sha256d($entropy);
        $math = $this->ecAdapter->getMath();

        // Convert byte string to padded binary string of 0/1's.
        $hashBits = str_pad($math->baseConvert($entHash->getBinary(), 256, 2), 256, '0', STR_PAD_LEFT);

        // Take $CSlen bits for the checksum
        $checksumBits = substr($hashBits, 0, $CSlen);

        return $checksumBits;
    }

    /**
     * @param Buffer $entropy
     * @return string
     */
    public function entropyToMnemonic(Buffer $entropy)
    {
        return implode(" ", $this->entropyToWords($entropy));
    }

    /**
     * @param $mnemonic
     * @return Buffer
     */
    public function mnemonicToEntropy($mnemonic)
    {
        $math = $this->ecAdapter->getMath();
        $words = explode(" ", $mnemonic);

        if ($math->mod(count($words), 3) !== 0) {
            throw new \InvalidArgumentException('Invalid mnemonic');
        }

        $bits = array();
        foreach ($words as $word) {
            $idx = $this->wordList->getIndex($word);
            $bits[] = str_pad($math->baseConvert($idx, 10, 2), 11, '0', STR_PAD_LEFT);
        }

        $bits = implode("", $bits);

        $CS = strlen($bits) / 33;
        $ENT = strlen($bits) - $CS;

        $csBits = substr($bits, -1 * $CS);
        $entBits = substr($bits, 0, -1 * $CS);

        $entropy = new Buffer($math->baseConvert($entBits, 2, 256), $ENT / 8);

        if ($csBits !== $this->calculateChecksum($entropy, $CS)) {
            throw new \InvalidArgumentException('Checksum does not match');
        }

        return $entropy;
    }
}
