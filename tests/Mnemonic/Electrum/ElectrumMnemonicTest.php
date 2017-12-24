<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Mnemonic\Electrum;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumMnemonic;
use BitWasp\Bitcoin\Mnemonic\Electrum\Wordlist\EnglishWordList;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ElectrumMnemonicTest extends AbstractTestCase
{
    public function testSpecificMnemonic()
    {
        $ec = Bitcoin::getEcAdapter();
        $mnemonicConv = new ElectrumMnemonic($ec, new EnglishWordList());

        $mnemonic = trim('teach start paradise collect blade chill gay childhood creek picture creator branch');
        $known_seed = 'dcb85458ec2fcaaac54b71fba90bd4a5';

        $this->assertEquals($known_seed, $mnemonicConv->mnemonicToEntropy($mnemonic)->getHex());
    }

    public function testEncodesEntropy()
    {
        $ec = Bitcoin::getEcAdapter();
        $m = new ElectrumMnemonic($ec, new EnglishWordList());

        $random = new Random();
        $bytes = $random->bytes(16);
        $words = $m->entropyToMnemonic($bytes);
        $entropy = $m->mnemonicToEntropy($words);

        $this->assertEquals($bytes, $entropy);
    }
}
