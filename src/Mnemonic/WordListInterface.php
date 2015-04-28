<?php

namespace BitWasp\Bitcoin\Mnemonic;

interface WordListInterface extends \Countable
{
    /**
     * @return string[]
     */
    public function getWords();

    /**
     * @param $index
     * @return string
     */
    public function getWord($index);

    /**
     * @param $word
     * @return integer
     */
    public function getIndex($word);
}
