<?php

declare(strict_types=1);

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
    public function create($entropySize = 512): string
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
    private function calculateChecksum(BufferInterface $entropy, int $CSlen): string
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
     * @return string[] - array of words from the word list
     */
    public function entropyToWords(BufferInterface $entropy): array
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

        $ENT = $entropy->getSize() * 8;
        $CS = $ENT / 32;

        $bits = gmp_strval($entropy->getGmp(), 2) . $this->calculateChecksum($entropy, $CS);
        $bits = str_pad($bits, ($ENT + $CS), '0', STR_PAD_LEFT);

        $result = [];
        foreach (str_split($bits, 11) as $bit) {
            $result[] = $this->wordList->getWord((int) bindec($bit));
        }

        return $result;
    }

    /**
     * @param BufferInterface $entropy
     * @return string
     */
    public function entropyToMnemonic(BufferInterface $entropy): string
    {
        return implode(' ', $this->entropyToWords($entropy));
    }

    /**
     * @param string $mnemonic
     * @return BufferInterface
     */
    public function mnemonicToEntropy(string $mnemonic): BufferInterface
    {
        $words = explode(' ', $mnemonic);

        // Mnemonic sizes are multiples of 3 words
        if (count($words) % 3 !== 0) {
            throw new \InvalidArgumentException('Invalid mnemonic');
        }

        // Build up $bits from the list of words
        $bits = '';
        foreach ($words as $word) {
            $idx = $this->wordList->getIndex($word);
            // Mnemonic bit sizes are multiples of 33 bits
            $bits .= str_pad(decbin($idx), 11, '0', STR_PAD_LEFT);
        }

        // Max entropy is 1024bytes; (1024×8)+((1024×8)÷32) = 8448 bits
        if (strlen($bits) > 8448) {
            throw new \InvalidArgumentException('Invalid mnemonic, too long');
        }

        // Every 32 bits of ENT adds a 1 CS bit.
        $CS = strlen($bits) / 33;
        $ENT = strlen($bits) - $CS;

        // Checksum bits
        $csBits = substr($bits, $ENT, $CS);

        // Split $ENT bits into 8 bit words to be packed
        assert($ENT % 8 === 0);
        $entArray = str_split(substr($bits, 0, $ENT), 8);
        $chars = [];
        for ($i = 0; $i < $ENT / 8; $i++) {
            $chars[] = (int) bindec($entArray[$i]);
        }

        // Check checksum
        $entropy = new Buffer(pack("C*", ...$chars));
        if (hash_equals($csBits, $this->calculateChecksum($entropy, $CS))) {
            return $entropy;
        } else {
            throw new \InvalidArgumentException('Checksum does not match');
        }
    }
}
