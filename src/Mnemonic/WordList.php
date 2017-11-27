<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Mnemonic;

abstract class WordList implements WordListInterface
{
    /**
     * @param int $index
     * @return string
     */
    public function getWord(int $index): string
    {
        $words = $this->getWords();
        if (!isset($words[$index])) {
            throw new \InvalidArgumentException(__CLASS__ . " does not contain a word for index [{$index}]");
        }

        return $words[$index];
    }
}
