<?php

namespace BitWasp\Bitcoin\Mnemonic;

use BitWasp\Buffertools\Buffer;

interface MnemonicInterface
{

    /**
     * @param Buffer $entropy
     * @return string[]
     */
    public function entropyToWords(Buffer $entropy);

    /**
     * @param Buffer $entropy
     * @return string
     */
    public function entropyToMnemonic(Buffer $entropy);

    /**
     * @param $mnemonic
     * @return Buffer
     */
    public function mnemonicToEntropy($mnemonic);
}
