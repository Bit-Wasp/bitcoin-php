<?php

namespace BitWasp\Bitcoin\Mnemonic\Electrum;


use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Mnemonic\MnemonicInterface;
use BitWasp\Buffertools\Buffer;

class ElectrumMnemonic implements MnemonicInterface
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
        $this->wordList = new ElectrumWordList();
    }

    public function entropyToWords(Buffer $entropy)
    {

    }

    public function entropyToMnemonic(Buffer $entropy)
    {

    }

    /**
     * @param string $mnemonic
     * @return Buffer
     */
    public function mnemonicToEntropy($mnemonic)
    {
        $math = $this->ecAdapter->getMath();
        $wordList = $this->wordList;

        $words = explode(" ", $mnemonic);
        $n = count($wordList);
        $out = '';

        $thirdWordCount = count($words) / 3;

        for ($i = 0; $i < $thirdWordCount; $i++) {
            $a = $math->mul(3, $i);
            list ($word1, $word2, $word3) = array_slice($words, $a, 3);

            $index1 = $wordList->getIndex($word1);
            $index2 = $wordList->getIndex($word2);
            $index3 = $wordList->getIndex($word3);

            $x = $math->add($index1,
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

            $out .= $math->decHex($x);
        }

        return Buffer::hex($out);
    }
}