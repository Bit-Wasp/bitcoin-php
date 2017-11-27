<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Mnemonic;

use BitWasp\Buffertools\BufferInterface;

interface MnemonicInterface
{

    /**
     * @param BufferInterface $entropy
     * @return string[]
     */
    public function entropyToWords(BufferInterface $entropy): array;

    /**
     * @param BufferInterface $entropy
     * @return string
     */
    public function entropyToMnemonic(BufferInterface $entropy): string;

    /**
     * @param string $mnemonic
     * @return BufferInterface
     */
    public function mnemonicToEntropy(string $mnemonic): BufferInterface;
}
