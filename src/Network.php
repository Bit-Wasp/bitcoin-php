<?php

namespace Afk11\Bitcoin;

use Afk11\Bitcoin\Crypto\Hash;

class Network implements NetworkInterface
{
    /**
     * @var string
     */
    protected $addressByte;

    /**
     * @var string
     */
    protected $privByte;

    /**
     * @var string
     */
    protected $p2shByte;

    /**
     * @var bool
     */
    protected $testnet;

    /**
     * @var null|string
     */
    protected $xpubByte = null;

    /**
     * @var null|string
     */
    protected $xprivByte = null;

    /**
     * Load basic data, throw exception if it's not provided
     *
     * @param $addressByte
     * @param $p2shByte
     * @param $privByte
     * @param bool $testnet
     * @throws \Exception
     */
    public function __construct($addressByte, $p2shByte, $privByte, $testnet = false)
    {
        if (! (ctype_xdigit($addressByte) and strlen($addressByte) == 2)) {
            throw new \Exception("address byte must be 1 hexadecimal byte");
        }

        if (! (ctype_xdigit($p2shByte) and strlen($p2shByte) == 2)) {
            throw new \Exception("p2sh byte must be 1 hexadecimal byte");
        }

        if (! (ctype_xdigit($privByte) and strlen($privByte) == 2)) {
            throw new \Exception("priv byte must be 1 hexadecimal byte");
        }

        if (! is_bool($testnet)) {
            throw new \Exception("Testnet parameter must be a boolean");
        }

        $this->addressByte = $addressByte;
        $this->p2shByte    = $p2shByte;
        $this->privByte    = $privByte;
        $this->testnet     = $testnet;
        return $this;
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
        if ($this->xpubByte == null) {
            throw new \Exception('No HD xpub byte was set');
        }

        return $this->xpubByte;
    }

    /**
     * Set version bytes for XPUB key
     *
     * @param $byte
     * @return $this
     */
    public function setHDPubByte($byte)
    {
        if (!empty($byte) and ctype_xdigit($byte) == true) {
            $this->xpubByte = $byte;
        }

        return $this;
    }

    /**
     * Get version bytes for XPRIV key
     *
     * @return null
     * @throws \Exception
     */
    public function getHDPrivByte()
    {
        if ($this->xprivByte == null) {
            throw new \Exception('No HD xpriv byte was set');
        }

        return $this->xprivByte;
    }

    /**
     * Set version bytes for XPRIV key
     *
     * @param $bytes
     * @return $this
     */
    public function setHDPrivByte($bytes)
    {
        if (!empty($bytes) and ctype_xdigit($bytes) == true) {
            $this->xprivByte = $bytes;
        }

        return $this;
    }

    /**
     * @param bool $binary
     * @return callable
     */
    public static function getHashFunction($binary = false)
    {
        return function($value) use ($binary) {
            $hash = Hash::sha256d($value, $binary);
            return $hash;
        };
    }
}
