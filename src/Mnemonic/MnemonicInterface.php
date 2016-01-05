<?php

namespace BitWasp\Bitcoin\Mnemonic;

use BitWasp\Buffertools\BufferInterface;

interface MnemonicInterface
{

    /**
     * @param BufferInterface $entropy
     * @return string[]
     */
    public function entropyToWords(BufferInterface $entropy);

    /**
     * @param BufferInterface $entropy
     * @return string
     */
    public function entropyToMnemonic(BufferInterface $entropy);

    /**
     * @param string $mnemonic
     * @return BufferInterface
     */
    public function mnemonicToEntropy($mnemonic);
}
