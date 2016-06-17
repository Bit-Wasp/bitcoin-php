<?php

namespace BitWasp\Bitcoin\Mnemonic\Electrum;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Mnemonic\MnemonicInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ElectrumMnemonic implements MnemonicInterface
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var ElectrumWordListInterface
     */
    private $wordList;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param ElectrumWordListInterface $wordList
     */
    public function __construct(EcAdapterInterface $ecAdapter, ElectrumWordListInterface $wordList)
    {
        $this->ecAdapter = $ecAdapter;
        $this->wordList = $wordList;
    }

    /**
     * @param BufferInterface $entropy
     * @return array
     * @throws \Exception
     */
    public function entropyToWords(BufferInterface $entropy)
    {
        $math = $this->ecAdapter->getMath();
        $n = gmp_init(count($this->wordList), 10);
        $wordArray = [];

        $chunks = $entropy->getSize() / 4;
        for ($i = 0; $i < $chunks; $i++) {
            $x = gmp_init($entropy->slice(4*$i, 4)->getInt(), 10);
            $index1 = $math->mod($x, $n);
            $index2 = $math->mod($math->add($math->div($x, $n), $index1), $n);
            $index3 = $math->mod($math->add($math->div($math->div($x, $n), $n), $index2), $n);

            $wordArray[] = $this->wordList->getWord(gmp_strval($index1, 10));
            $wordArray[] = $this->wordList->getWord(gmp_strval($index2, 10));
            $wordArray[] = $this->wordList->getWord(gmp_strval($index3, 10));
        }

        return $wordArray;
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
        $wordList = $this->wordList;

        $words = explode(' ', $mnemonic);
        $n = gmp_init(count($wordList), 10);
        $out = '';

        $thirdWordCount = count($words) / 3;

        for ($i = 0; $i < $thirdWordCount; $i++) {
            list ($word1, $word2, $word3) = array_slice($words, 3 * $i, 3);

            $index1 = gmp_init($wordList->getIndex($word1), 10);
            $index2 = gmp_init($wordList->getIndex($word2), 10);
            $index3 = gmp_init($wordList->getIndex($word3), 10);

            $x = $math->add(
                $index1,
                $math->add(
                    $math->mul(
                        $n,
                        $math->mod($index2 - $index1, $n)
                    ),
                    $math->mul(
                        $n,
                        $math->mul(
                            $n,
                            $math->mod($index3 - $index2, $n)
                        )
                    )
                )
            );

            $out .= str_pad($math->decHex(gmp_strval($x, 10)), 8, '0', STR_PAD_LEFT);
        }

        return Buffer::hex($out);
    }
}
