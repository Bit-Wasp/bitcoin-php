<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39WordListInterface;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

class Bip39MnemonicTest extends AbstractTestCase
{

    public function getWordList($language)
    {
        $language = strtolower($language);

        if ($language == 'english') {
            return new EnglishWordList();
        }

        throw new \InvalidArgumentException('Unknown wordlist');
    }

    public function getBip39Vectors()
    {
        $file = json_decode(file_get_contents(__DIR__ . '/../../Data/bip39.json'), true);
        $vectors = [];

        foreach ($file as $list => $testSet) {
            $wordList = $this->getWordList($list);

            foreach ($testSet as $set) {
                $vectors[] = [
                    $wordList,
                    Buffer::hex($set[0]),
                    $set[1],
                    Buffer::hex($set[2])
                ];
            }
        }

        return $vectors;
    }

    /**
     * @dataProvider getBip39Vectors
     * @param $list
     * @param Buffer $entropy
     * @param $eMnemonic
     * @param Buffer $eSeed
     */
    public function testEntropyToMnemonic(Bip39WordListInterface $list, Buffer $entropy, $eMnemonic, Buffer $eSeed)
    {
        $ec = Bitcoin::getEcAdapter();
        $bip39 = new Bip39Mnemonic($ec, $list);

        $mnemonic = $bip39->entropyToMnemonic($entropy);
        $this->assertEquals($eMnemonic, $mnemonic);
    }

    /**
     * @dataProvider getBip39Vectors
     * @param Bip39WordListInterface $list
     * @param Buffer $eEntropy
     * @param $mnemonic
     * @param Buffer $eSeed
     */
    public function testMnemonicToEntropy(Bip39WordListInterface $list, Buffer $eEntropy, $mnemonic, Buffer $eSeed)
    {
        $ec = Bitcoin::getEcAdapter();
        $bip39 = new Bip39Mnemonic($ec, $list);

        $entropy = $bip39->mnemonicToEntropy($mnemonic);
        $this->assertEquals($eEntropy->getBinary(), $entropy->getBinary());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid mnemonic
     */
    public function testIncorrectWordCount()
    {
        $ec = Bitcoin::getEcAdapter();
        $list = new EnglishWordList();
        $bip39 = new Bip39Mnemonic($ec, $list);
        $mnemonic = 'letter advice';
        $bip39->mnemonicToEntropy($mnemonic);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Checksum does not match
     */
    public function testFailsOnInvalidChecksum()
    {
        $ec = Bitcoin::getEcAdapter();
        $list = new EnglishWordList();
        $bip39 = new Bip39Mnemonic($ec, $list);
        $mnemonic = 'jelly better achieve collect unaware mountain thought cargo oxygen act hood oxygen';
        $bip39->mnemonicToEntropy($mnemonic);
    }
}