<?php

namespace Bitcoin;

/**
 * Class Network
 * @package Bitcoin
 */
class Network implements NetworkInterface
{
    /**
     * @var
     */
    protected $address_byte;

    /**
     * @var
     */
    protected $priv_byte;

    /**
     * @var
     */
    protected $p2sh_byte;

    /**
     * @var bool
     */
    protected $testnet;

    /**
     * @var null
     */
    protected $xpub_byte = null;

    /**
     * @var null
     */
    protected $xpriv_byte = null;

    /**
     * Load basic data, throw exception if it's not provided
     *
     * @param $addressByte
     * @param $p2shByte
     * @param $privByte
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

        $this->address_byte = $addressByte;
        $this->p2sh_byte = $p2shByte;
        $this->priv_byte = $privByte;
        $this->testnet = $testnet;
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
        return $this->address_byte;
    }

    /**
     * @inheritdoc
     */
    public function getPrivByte()
    {
        return $this->priv_byte;
    }

    /**
     * @inheritdoc
     */
    public function getP2shByte()
    {
        return $this->p2sh_byte;
    }

    /**
     * Get version bytes for XPUB key
     *
     * @return string
     * @throws \Exception
     */
    public function getHDPubByte()
    {
        if ($this->xpub_byte == null) {
            throw new \Exception('No HD xpub byte was set');
        }

        return $this->xpub_byte;
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
            $this->xpub_byte = $byte;
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
        if ($this->xpriv_byte == null) {
            throw new \Exception('No HD xpriv byte was set');
        }

        return $this->xpriv_byte;
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
            $this->xpriv_byte = $bytes;
        }

        return $this;
    }
}
