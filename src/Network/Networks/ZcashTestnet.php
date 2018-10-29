<?php

namespace BitWasp\Bitcoin\Network\Networks;

use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Script\ScriptType;

class ZcashTestnet extends Network
{
    /**
     * {@inheritdoc}
     * @see Network::$base58PrefixMap
     */
    protected $base58PrefixMap = [
        // https://github.com/zcash/zcash/blob/master/src/chainparams.cpp#L139-L144
        self::BASE58_ADDRESS_P2PKH => "1d25",
        self::BASE58_ADDRESS_P2SH => "1cba",
        self::BASE58_WIF => "ef",
    ];

    /**
     * {@inheritdoc}
     * @see Network::$bip32PrefixMap
     */
    protected $bip32PrefixMap = [
        // https://github.com/zcash/zcash/blob/master/src/chainparams.cpp#L146-L147
        self::BIP32_PREFIX_XPUB => "043587cf",
        self::BIP32_PREFIX_XPRV => "04358394",
    ];

    /**
     * {@inheritdoc}
     * @see Network::$bip32ScriptTypeMap
     */
    protected $bip32ScriptTypeMap = [
        self::BIP32_PREFIX_XPUB => ScriptType::P2PKH,
        self::BIP32_PREFIX_XPRV => ScriptType::P2PKH,
    ];

    /**
     * {@inheritdoc}
     * @see Network::$signedMessagePrefix
     */
    protected $signedMessagePrefix = "Zcash Signed Message";

    /**
     * {@inheritdoc}
     * @see Network::$p2pMagic
     */
    // https://github.com/zcash/zcash/blob/master/src/chainparams.cpp#L111-L114
    protected $p2pMagic = "bff91afa";
}
