<?php

namespace BitWasp\Bitcoin\Mnemonic\Bip39;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Bip39Mnemonic implements MnemonicInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var Bip39WordListInterface
     */
    private $wordList;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param Bip39WordListInterface $wordList
     */
    public function __construct(EcAdapterInterface $ecAdapter, Bip39WordListInterface $wordList)
    {
        $this->ecAdapter = $ecAdapter;
        $this->wordList = $wordList;
    }

    /**
     * Creates a new Bip39 mnemonic string.
     *
     * @param int $entropySize
     * @return string
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function create($entropySize = 512)
    {
        $random = new Random();
        $entropy = $random->bytes($entropySize / 8);

        return $this->entropyToMnemonic($entropy);
    }

    /**
     * @param BufferInterface $entropy
     * @param integer $CSlen
     * @return string
     */
    private function calculateChecksum(BufferInterface $entropy, $CSlen)
    {
        $entHash = Hash::sha256($entropy);

        // Convert byte string to padded binary string of 0/1's.
        $hashBits = str_pad(gmp_strval($entHash->getGmp(), 2), 256, '0', STR_PAD_LEFT);

        // Take $CSlen bits for the checksum
        $checksumBits = substr($hashBits, 0, $CSlen);

        return $checksumBits;
    }

    /**
     * @param BufferInterface $entropy
     * @return array
     */
    public function entropyToWords(BufferInterface $entropy)
    {
        if ($entropy->getSize() === 0) {
            throw new \InvalidArgumentException('Invalid entropy, empty');
        }
        if ($entropy->getSize() > 1024) {
            throw new \InvalidArgumentException('Invalid entropy, max 1024 bytes');
        }
        if ($entropy->getSize() % 4 !== 0) {
            throw new \InvalidArgumentException('Invalid entropy, must be multitude of 4 bytes');
        }

        $math = $this->ecAdapter->getMath();

        $ENT = $entropy->getSize() * 8;
        $CS = $ENT / 32;

        $bits = gmp_strval($entropy->getGmp(), 2) . $this->calculateChecksum($entropy, $CS);
        $bits = str_pad($bits, ($ENT + $CS), '0', STR_PAD_LEFT);

        $result = [];
        foreach (str_split($bits, 11) as $bit) {
            $idx = $math->baseConvert($bit, 2, 10);
            $result[] = $this->wordList->getWord($idx);
        }

        return $result;
    }

    /**
     * @param BufferInterface $entropy
     * @return string
     */
    public function entropyToMnemonic(BufferInterface $entropy)
    {
        return implode(' ', $this->entropyToWords($entropy));
    }

    /**
     * @param string $mnemonic
     * @return BufferInterface
     */
    public function mnemonicToEntropy($mnemonic)
    {
        $math = $this->ecAdapter->getMath();
        $words = explode(' ', $mnemonic);

        if (count($words) % 3 !== 0) {
            throw new \InvalidArgumentException('Invalid mnemonic');
        }

        $bits = array();
        foreach ($words as $word) {
            $idx = $this->wordList->getIndex($word);
            $bits[] = str_pad($math->baseConvert($idx, 10, 2), 11, '0', STR_PAD_LEFT);
        }

        $bits = implode('', $bits);

        // max entropy is 1024; (1024×8)+((1024×8)÷32) = 8448
        if (strlen($bits) > 8448) {
            throw new \InvalidArgumentException('Invalid mnemonic, too long');
        }

        $CS = strlen($bits) / 33;
        $ENT = strlen($bits) - $CS;

        $csBits = substr($bits, -1 * $CS);
        $entBits = substr($bits, 0, -1 * $CS);

        $binary = '';
        $bitsInChar = 8;
        for ($i = 0; $i < $ENT; $i += $bitsInChar) {
            // Extract 8 bits at a time, convert to hex, pad, and convert to binary.
            $eBits = substr($entBits, $i, $bitsInChar);
            $binary .= pack("H*", (str_pad($math->baseConvert($eBits, 2, 16), 2, '0', STR_PAD_LEFT)));
        }

        $entropy = new Buffer($binary, null, $math);
        if ($csBits !== $this->calculateChecksum($entropy, $CS)) {
            throw new \InvalidArgumentException('Checksum does not match');
        }

        return $entropy;
    }
}
