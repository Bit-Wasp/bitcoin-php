<?php

namespace BitWasp\Bitcoin\Network;

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
     * @var string
     */
    private $witnessV0KeyHash;

    /**
     * @var string
     */
    private $witnessV0ScriptHash;

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
        if (!(ctype_xdigit($addressByte) && strlen($addressByte) === 2)) {
            throw new \InvalidArgumentException('address byte must be 1 hexadecimal byte');
        }

        if (!(ctype_xdigit($p2shByte) && strlen($p2shByte) === 2)) {
            throw new \InvalidArgumentException('p2sh byte must be 1 hexadecimal byte');
        }

        if (!(ctype_xdigit($privByte) && strlen($privByte) === 2)) {
            throw new \InvalidArgumentException('priv byte must be 1 hexadecimal byte');
        }

        if (!is_bool($testnet)) {
            throw new \InvalidArgumentException('Testnet parameter must be a boolean');
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
     * @param string $bytes
     * @return $this
     */
    public function setHDPubByte($bytes)
    {
        if (strlen($bytes) === 8 && ctype_xdigit($bytes) === true) {
            $this->xpubByte = $bytes;
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
        if (strlen($bytes) === 8 && ctype_xdigit($bytes) === true) {
            $this->xprivByte = $bytes;
        }

        return $this;
    }

    /**
     * @param string $witnessByte
     * @return $this
     */
    public function setP2WPKHByte($witnessByte)
    {
        if (!(ctype_xdigit($witnessByte) && strlen($witnessByte) === 2)) {
            throw new \InvalidArgumentException('witness byte must be 1 hexadecimal byte');
        }

        $this->witnessV0KeyHash = $witnessByte;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getP2WPKHByte()
    {
        if ($this->witnessV0KeyHash === null) {
            throw new \Exception('No segnet byte was set');
        }

        return $this->witnessV0KeyHash;
    }

    /**
     * @param string $witnessByte
     * @return $this
     */
    public function setP2WSHByte($witnessByte)
    {
        if (!(ctype_xdigit($witnessByte) && strlen($witnessByte) === 2)) {
            throw new \InvalidArgumentException('witness byte must be 1 hexadecimal byte');
        }

        $this->witnessV0ScriptHash  = $witnessByte;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getP2WSHByte()
    {
        if ($this->witnessV0ScriptHash === null) {
            throw new \Exception('No P2WPS was set');
        }

        return $this->witnessV0ScriptHash;
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
