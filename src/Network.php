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
     * @param $address_byte
     * @param $p2sh_byte
     * @param $priv_byte
     * @throws \Exception
     */
    public function __construct($address_byte, $p2sh_byte, $priv_byte, $testnet = false)
    {
        foreach (array('address_byte', 'p2sh_byte', 'priv_byte') as $required_byte) {
            if (ctype_xdigit($$required_byte) and strlen($$required_byte) == 2) {
                $this->$required_byte = $$required_byte;
            } else {
                throw new \Exception("$required_byte must be 1 hexadecimal byte");
            }
        }

        if (! is_bool($testnet)) {
            throw new \Exception("Testnet parameter must be a boolean");
        }

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
