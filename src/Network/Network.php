<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter;
use BitWasp\Bitcoin\Exceptions\MissingBase58Prefix;
use BitWasp\Bitcoin\Exceptions\MissingBech32Prefix;
use BitWasp\Bitcoin\Exceptions\MissingBip32Prefix;
use BitWasp\Bitcoin\Exceptions\MissingNetworkParameter;

class Network implements NetworkInterface
{
    const BECH32_PREFIX_SEGWIT = "segwit";

    const BASE58_ADDRESS_P2PKH = "p2pkh";
    const BASE58_ADDRESS_P2SH = "p2sh";
    const BASE58_WIF = "wif";
    const BIP32_PREFIX_XPUB = "xpub";
    const BIP32_PREFIX_XPRV = "xprv";

    /**
     * @var array map of base58 address type to byte
     */
    protected $base58PrefixMap = [];

    /**
     * @var array map of bech32 address type to HRP
     */
    protected $bech32PrefixMap = [];

    /**
     * @var array map of bip32 type to bytes
     */
    protected $bip32PrefixMap = [];

    /**
     * @var array map of bip32 key type to script type
     */
    protected $bip32ScriptTypeMap = [];

    /**
     * @var string - message prefix for bitcoin signed messages
     */
    protected $signedMessagePrefix;

    /**
     * @var string - 4 bytes for p2p magic
     */
    protected $p2pMagic;

    /**
     * @param string $field - name of field being validated
     * @param mixed $value - we check this value
     * @param int $length - length we require
     */
    private function validateHexString($field, $value, $length)
    {
        if (!is_string($value) || strlen($value) !== 2 * $length) {
            throw new InvalidNetworkParameter("{$field} must be a {$length} byte hex string");
        }

        if (!ctype_xdigit($value)) {
            throw new InvalidNetworkParameter("{$field} prefix must be a valid hex string");
        }
    }

    /**
     * Network constructor.
     */
    public function __construct()
    {
        if (null !== $this->p2pMagic) {
            $this->validateHexString("P2P magic", $this->p2pMagic, 4);
        }

        foreach ($this->base58PrefixMap as $type => $byte) {
            $this->validateHexString("{$type} base58 prefix", $byte, 1);
        }

        foreach ($this->bip32PrefixMap as $type => $bytes) {
            $this->validateHexString("{$type} bip32 prefix", $bytes, 4);
        }

        if (count($this->bip32ScriptTypeMap) !== count($this->bip32PrefixMap)) {
            throw new InvalidNetworkParameter("BIP32 prefixes not configured correctly");
        }
    }

    /**
     * @param string $prefixType
     * @return bool
     */
    protected function hasBase58Prefix($prefixType)
    {
        return array_key_exists($prefixType, $this->base58PrefixMap);
    }

    /**
     * @param string $prefixType
     * @return string
     * @throws MissingBase58Prefix
     */
    protected function getBase58Prefix($prefixType)
    {
        if (!$this->hasBase58Prefix($prefixType)) {
            throw new MissingBase58Prefix();
        }
        return $this->base58PrefixMap[$prefixType];
    }

    /**
     * @param string $prefixType
     * @return bool
     */
    protected function hasBech32Prefix($prefixType)
    {
        return array_key_exists($prefixType, $this->bech32PrefixMap);
    }

    /**
     * @param string $prefixType
     * @return string
     * @throws MissingBech32Prefix
     */
    protected function getBech32Prefix($prefixType)
    {
        if (!$this->hasBech32Prefix($prefixType)) {
            throw new MissingBech32Prefix();
        }
        return $this->bech32PrefixMap[$prefixType];
    }

    /**
     * @param string $prefixType
     * @return bool
     */
    protected function hasBip32Prefix($prefixType)
    {
        return array_key_exists($prefixType, $this->bip32PrefixMap);
    }

    /**
     * @param $prefixType
     * @return mixed
     * @throws MissingBip32Prefix
     */
    protected function getBip32Prefix($prefixType)
    {
        if (!$this->hasBip32Prefix($prefixType)) {
            throw new MissingBip32Prefix();
        }
        return $this->bip32PrefixMap[$prefixType];
    }

    /**
     * @return string
     * @throws MissingNetworkParameter
     * @see NetworkInterface::getSignedMessageMagic
     */
    public function getSignedMessageMagic()
    {
        if (null === $this->signedMessagePrefix) {
            throw new MissingNetworkParameter("Missing magic string for signed message");
        }
        return $this->signedMessagePrefix;
    }

    /**
     * @return string
     * @throws MissingNetworkParameter
     * @see NetworkInterface::getNetMagicBytes()
     */
    public function getNetMagicBytes()
    {
        if (null === $this->p2pMagic) {
            throw new MissingNetworkParameter("Missing network magic bytes");
        }
        return $this->p2pMagic;
    }

    /**
     * @return string
     * @throws MissingBase58Prefix
     */
    public function getPrivByte()
    {
        return $this->getBase58Prefix(self::BASE58_WIF);
    }

    /**
     * @return string
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getAddressByte()
     */
    public function getAddressByte()
    {
        return $this->getBase58Prefix(self::BASE58_ADDRESS_P2PKH);
    }

    /**
     * @return string
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getP2shByte()
     */
    public function getP2shByte()
    {
        return $this->getBase58Prefix(self::BASE58_ADDRESS_P2SH);
    }

    /**
     * @return mixed|string
     * @throws MissingBip32Prefix
     * @see NetworkInterface::getHDPubByte()
     */
    public function getHDPubByte()
    {
        return $this->getBip32Prefix(self::BIP32_PREFIX_XPUB);
    }

    /**
     * @return mixed|string
     * @throws MissingBip32Prefix
     * @see NetworkInterface::getHDPrivByte()
     */
    public function getHDPrivByte()
    {
        return $this->getBip32Prefix(self::BIP32_PREFIX_XPRV);
    }

    /**
     * @return string
     * @throws MissingBech32Prefix
     * @see NetworkInterface::getSegwitBech32Prefix()
     */
    public function getSegwitBech32Prefix()
    {
        return $this->getBech32Prefix(self::BECH32_PREFIX_SEGWIT);
    }
}
