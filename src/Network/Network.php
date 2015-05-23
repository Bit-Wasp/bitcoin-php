<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;

class Network implements NetworkInterface
{
    /**
     * @var string
     */
    private $addressByte;

    /**
     * @var string
     */
    private $privByte;

    /**
     * @var string
     */
    private $p2shByte;

    /**
     * @var bool
     */
    private $testnet;

    /**
     * @var null|string
     */
    private $xpubByte;

    /**
     * @var null|string
     */
    private $xprivByte;

    /**
     * @var string
     */
    private $netMagicBytes;

    /**
     * Load basic data, throw exception if it's not provided
     *
     * @param string $addressByte
     * @param string $p2shByte
     * @param string $privByte
     * @param bool $testnet
     * @throws \Exception
     */
    public function __construct($addressByte, $p2shByte, $privByte, $testnet = false)
    {
        if (!(ctype_xdigit($addressByte) && strlen($addressByte) == 2)) {
            throw new \Exception("address byte must be 1 hexadecimal byte");
        }

        if (!(ctype_xdigit($p2shByte) && strlen($p2shByte) == 2)) {
            throw new \Exception("p2sh byte must be 1 hexadecimal byte");
        }

        if (!(ctype_xdigit($privByte) && strlen($privByte) == 2)) {
            throw new \Exception("priv byte must be 1 hexadecimal byte");
        }

        if (!is_bool($testnet)) {
            throw new \Exception("Testnet parameter must be a boolean");
        }

        $this->addressByte = $addressByte;
        $this->p2shByte = $p2shByte;
        $this->privByte = $privByte;
        $this->testnet = $testnet;
    }

    /**
     * @inheritdoc
     */
    public function isTestnet()
    {
        return $this->testnet;
    }

    /**
     * @inheritdoc
     */
    public function getAddressByte()
    {
        return $this->addressByte;
    }

    /**
     * @inheritdoc
     */
    public function getPrivByte()
    {
        return $this->privByte;
    }

    /**
     * @inheritdoc
     */
    public function getP2shByte()
    {
        return $this->p2shByte;
    }

    /**
     * Get version bytes for XPUB key
     *
     * @return string
     * @throws \Exception
     */
    public function getHDPubByte()
    {
        if ($this->xpubByte === null) {
            throw new \Exception('No HD xpub byte was set');
        }

        return $this->xpubByte;
    }

    /**
     * Set version bytes for XPUB key
     *
     * @param string $byte
     * @return $this
     */
    public function setHDPubByte($byte)
    {
        if (!empty($byte) && ctype_xdigit($byte) === true) {
            $this->xpubByte = $byte;
        }

        return $this;
    }

    /**
     * Get version bytes for XPRIV key
     *
     * @return string
     * @throws \Exception
     */
    public function getHDPrivByte()
    {
        if ($this->xprivByte === null) {
            throw new \Exception('No HD xpriv byte was set');
        }

        return $this->xprivByte;
    }

    /**
     * Set version bytes for XPRIV key
     *
     * @param string $bytes
     * @return $this
     */
    public function setHDPrivByte($bytes)
    {
        if (!empty($bytes) && ctype_xdigit($bytes) === true) {
            $this->xprivByte = $bytes;
        }

        return $this;
    }

    /**
     * @param string $bytes
     * @return $this
     */
    public function setNetMagicBytes($bytes)
    {
        $this->netMagicBytes = $bytes;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getNetMagicBytes()
    {
        if ($this->netMagicBytes === null) {
            throw new \Exception('No network magic bytes were set');
        }

        return $this->netMagicBytes;
    }
}
