<?php

namespace BitWasp\Bitcoin\Mnemonic;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Mnemonic\BIP39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumMnemonic;

class MnemonicFactory
{
    /**
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumMnemonic
     */
    public static function electrum(EcAdapterInterface $ecAdapter = null)
    {
        return new ElectrumMnemonic($ecAdapter ?: Bitcoin::getEcAdapter());
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     * @return Bip39Mnemonic
     */
    public static function bip39(EcAdapterInterface $ecAdapter = null)
    {
        return new Bip39Mnemonic($ecAdapter ?: Bitcoin::getEcAdapter());
    }
}