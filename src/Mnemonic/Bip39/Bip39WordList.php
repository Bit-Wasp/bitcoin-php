<?php

namespace BitWasp\Bitcoin\Mnemonic\Bip39;


use BitWasp\Bitcoin\Mnemonic\WordList;

class Bip39WordList extends WordList
{
    /**
     * @var array
     */
    private $wordsFlipped;

    /**
     * @return array
     */
    public function getWords()
    {
        return array();
    }

    /**
     * @param $word
     * @return mixed
     */
    public function getIndex($word)
    {
        // create a flipped word list to speed up the searching of words
        if ($this->wordsFlipped == null) {
            $this->wordsFlipped = array_flip($this->getWords());
        }

        if (!isset($this->wordsFlipped[$word])) {
            throw new \InvalidArgumentException(__CLASS__ . " does not contain word  [{$word}]");
        }

        return $this->wordsFlipped[$word];
    }
}
