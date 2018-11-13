<?php

declare(strict_types=1);

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
     * @param string $value - we check this value
     * @throws InvalidNetworkParameter
     */
    private function validateHexStringRepresentsBytes(string $field, string $value)
    {
        if (!is_string($value)) {
            throw new InvalidNetworkParameter("{$field} must be a string");
        }

        $length = strlen($value);

        if ($length == 0) {
            throw new InvalidNetworkParameter("{$field} must be non-empty string");
        }

        if ($length % 2 !== 0) {
            throw new InvalidNetworkParameter("{$field} must have even number of characters (hex representing bytes)");
        }

        if (!ctype_xdigit($value)) {
            throw new InvalidNetworkParameter("{$field} prefix must be a valid hex string");
        }
    }

    /**
     * Network constructor.
     * @throws InvalidNetworkParameter
     */
    public function __construct()
    {
        if (null !== $this->p2pMagic) {
            $this->validateHexStringRepresentsBytes("P2P magic", $this->p2pMagic);
        }

        foreach ($this->base58PrefixMap as $type => $byte) {
            $this->validateHexStringRepresentsBytes("{$type} base58 prefix", $byte);
        }

        foreach ($this->bip32PrefixMap as $type => $bytes) {
            $this->validateHexStringRepresentsBytes("{$type} bip32 prefix", $bytes);
        }

        if (count($this->bip32ScriptTypeMap) !== count($this->bip32PrefixMap)) {
            throw new InvalidNetworkParameter("BIP32 prefixes not configured correctly. Number of items does not match.");
        }
    }

    /**
     * @param string $prefixType
     * @return bool
     */
    protected function hasBase58Prefix(string $prefixType): bool
    {
        return array_key_exists($prefixType, $this->base58PrefixMap);
    }

    /**
     * @param string $prefixType
     * @return string
     * @throws MissingBase58Prefix
     */
    protected function getBase58Prefix(string $prefixType): string
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
    protected function hasBech32Prefix(string $prefixType): bool
    {
        return array_key_exists($prefixType, $this->bech32PrefixMap);
    }

    /**
     * @param string $prefixType
     * @return string
     * @throws MissingBech32Prefix
     */
    protected function getBech32Prefix(string $prefixType): string
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
    protected function hasBip32Prefix(string $prefixType): bool
    {
        return array_key_exists($prefixType, $this->bip32PrefixMap);
    }

    /**
     * @param string $prefixType
     * @return string
     * @throws MissingBip32Prefix
     */
    protected function getBip32Prefix(string $prefixType): string
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
    public function getSignedMessageMagic(): string
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
    public function getNetMagicBytes(): string
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
    public function getPrivByte(): string
    {
        return $this->getBase58Prefix(self::BASE58_WIF);
    }

    /**
     * @return string
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getAddressByte()
     */
    public function getAddressByte(): string
    {
        return $this->getBase58Prefix(self::BASE58_ADDRESS_P2PKH);
    }
    /**
     * @return int
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getAddressPrefixLength()
     */
    public function getAddressPrefixLength(): int
    {
        return strlen($this->getAddressByte()) / 2;
    }

    /**
     * @return string
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getP2shByte()
     */
    public function getP2shByte(): string
    {
        return $this->getBase58Prefix(self::BASE58_ADDRESS_P2SH);
    }

    /**
     * @return int
     * @throws MissingBase58Prefix
     * @see NetworkInterface::getP2shPrefixLength()
     */
    public function getP2shPrefixLength(): int
    {
        return strlen($this->getP2shByte()) / 2;
    }

    /**
     * @return string
     * @throws MissingBip32Prefix
     * @see NetworkInterface::getHDPubByte()
     */
    public function getHDPubByte(): string
    {
        return $this->getBip32Prefix(self::BIP32_PREFIX_XPUB);
    }

    /**
     * @return string
     * @throws MissingBip32Prefix
     * @see NetworkInterface::getHDPrivByte()
     */
    public function getHDPrivByte(): string
    {
        return $this->getBip32Prefix(self::BIP32_PREFIX_XPRV);
    }

    /**
     * @return string
     * @throws MissingBech32Prefix
     * @see NetworkInterface::getSegwitBech32Prefix()
     */
    public function getSegwitBech32Prefix(): string
    {
        return $this->getBech32Prefix(self::BECH32_PREFIX_SEGWIT);
    }
}
