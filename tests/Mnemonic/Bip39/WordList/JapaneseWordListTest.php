<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Mnemonic\Bip39\WordList;

use BitWasp\Bitcoin\Tests\AbstractTestCase;

class JapaneseWordListTest extends AbstractTestCase
{
    public function testGetWordList()
    {
        $wl = new \BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\JapaneseWordList();
        $this->assertEquals(2048, count($wl));
        $this->assertEquals(2048, count($wl->getWords()));
    }

    public function testUnknownWord()
    {
        $wl = new \BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\JapaneseWordList();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Wordlist does not contain a word for index [101010101]");
        $wl->getWord(101010101);
    }

    public function testExceptionOutOfRange()
    {
        $wl = new \BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\JapaneseWordList();

        $word = $wl->getIndex('あいだ');
        $this->assertIsInt($word);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Wordlist does not contain word あいあいあい");
        $wl->getIndex('あいあいあい');
    }
}
