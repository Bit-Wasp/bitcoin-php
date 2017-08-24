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
     * @var null|string
     */
    private $segwitAddrPrefix;

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

    /**
     * @param string $hrp
     * @return $this
     */
    public function setSegwitBech32Prefix($hrp)
    {
        if ($hrp !== strtoupper($hrp) && $hrp !== strtolower($hrp)) {
            throw new \RuntimeException("Bech32 prefix for segwit address contains mixed case characters");
        }

        $this->segwitAddrPrefix = $hrp;
        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getSegwitBech32Prefix()
    {
        if ($this->segwitAddrPrefix === null) {
            throw new \Exception("No bech32 prefix for segwit addresses set");
        }

        return $this->segwitAddrPrefix;
    }
}
