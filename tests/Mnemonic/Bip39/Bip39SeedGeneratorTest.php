<?php

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;

class Bip39SeedGeneratorTest extends AbstractBip39Case
{
    public function testMnemonicToSeed()
    {
        $ec = Bitcoin::getEcAdapter();
        $seedGenerator = new Bip39SeedGenerator(new Bip39Mnemonic($ec, new EnglishWordList()));

    }
}