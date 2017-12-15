<?php

namespace BitWasp\Bitcoin\Network\Networks;

use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Script\ScriptType;

class Dogecoin extends Network
{
    /**
     * {@inheritdoc}
     * @see Network::$base58PrefixMap
     */
    protected $base58PrefixMap = [
        self::BASE58_ADDRESS_P2PKH => "1e",
        self::BASE58_ADDRESS_P2SH => "16",
        self::BASE58_WIF => "9e",
    ];

    /**
     * {@inheritdoc}
     * @see Network::$bip32PrefixMap
     */
    protected $bip32PrefixMap = [
        self::BIP32_PREFIX_XPUB => "02facafd",
        self::BIP32_PREFIX_XPRV => "02fac398",
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
    protected $signedMessagePrefix = "Dogecoin Signed Message";

    /**
     * {@inheritdoc}
     * @see Network::$p2pMagic
     */
    protected $p2pMagic = "c0c0c0c0";
}
