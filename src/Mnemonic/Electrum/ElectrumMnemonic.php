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
        $n = count($this->wordList);
        $wordArray = [];

        $chunks = $entropy->getSize() / 4;
        for ($i = 0; $i < $chunks; $i++) {
            $x = $entropy->slice(4*$i, 4)->getInt();
            $index1 = $math->mod($x, $n);
            $index2 = $math->mod($math->add($math->div($x, $n), $index1), $n);
            $index3 = $math->mod($math->add($math->div($math->div($x, $n), $n), $index2), $n);

            $wordArray[] = $this->wordList->getWord($index1);
            $wordArray[] = $this->wordList->getWord($index2);
            $wordArray[] = $this->wordList->getWord($index3);
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
        $n = count($wordList);
        $out = '';

        $thirdWordCount = count($words) / 3;

        for ($i = 0; $i < $thirdWordCount; $i++) {
            list ($word1, $word2, $word3) = array_slice($words, $math->mul(3, $i), 3);

            $index1 = $wordList->getIndex($word1);
            $index2 = $wordList->getIndex($word2);
            $index3 = $wordList->getIndex($word3);

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

            $out .= str_pad($math->decHex($x), 8, '0', STR_PAD_LEFT);
        }

        return Buffer::hex($out);
    }
}
