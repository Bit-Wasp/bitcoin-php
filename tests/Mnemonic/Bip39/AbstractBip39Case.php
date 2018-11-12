<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;

use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39WordListInterface;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;

abstract class AbstractBip39Case extends AbstractTestCase
{
    /**
     * @param string $language
     * @return Bip39WordListInterface
     */
    public function getWordList(string $language): Bip39WordListInterface
    {
        switch (strtolower($language)) {
            case 'english':
                return new EnglishWordList();
            default:
                throw new \InvalidArgumentException('Unknown wordlist');
        }
    }

    /**
     * @return array[]
     */
    public function getBip39Vectors(): array
    {
        $file = json_decode($this->dataFile('bip39.json'), true);
        $vectors = [];

        $ec = $this->safeEcAdapter();
        foreach ($file as $list => $testSet) {
            $bip39 = new Bip39Mnemonic($ec, $this->getWordList($list));

            foreach ($testSet as $set) {
                $vectors[] = [
                    $bip39,
                    Buffer::hex($set[0]),
                    $set[1],
                    Buffer::hex($set[2])
                ];
            }
        }

        return $vectors;
    }
}
