<?php

namespace BitWasp\Bitcoin\Network\Networks;

use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Script\ScriptType;

class Viacoin extends Network
{
    /**
     * @var array map of base58 address type to byte
     */
    protected $base58PrefixMap = [
        self::BASE58_ADDRESS_P2PKH => "47",
        self::BASE58_ADDRESS_P2SH => "21",
        self::BASE58_WIF => "c7",
    ];

    /**
     * {@inheritdoc}
     * @see Network::$bech32PrefixMap
     */
    protected $bech32PrefixMap = [
        self::BECH32_PREFIX_SEGWIT => "via",
    ];

    /**
     * @var array map of bip32 type to bytes
     */
    protected $bip32PrefixMap = [
        self::BIP32_PREFIX_XPUB => "0488b21e",
        self::BIP32_PREFIX_XPRV => "0488ade4",
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
     * @var string - message prefix for bitcoin signed messages
     */
    protected $signedMessagePrefix = "Viacoin Signed Message";

    /**
     * @var string - 4 bytes for p2p magic
     */
    protected $p2pMagic = "cbc6680f";
}
