<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:09
 */

namespace Bitcoin;


class Network implements NetworkInterface {

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
    public function __construct($address_byte, $p2sh_byte, $priv_byte, $testnet = FALSE)
    {
        foreach (array('address_byte', 'p2sh_byte', 'priv_byte') as $required_byte) {
            if (ctype_xdigit($$required_byte) and strlen($$required_byte) == 2) {
                $this->$required_byte = $$required_byte;
            } else {
                throw new \Exception("$required_byte must be 1 hexadecimal byte");
            }
        }

        if ( ! is_bool($testnet)) {
            throw new \Exception("Testnet parameter must be a boolean");
        }

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

} 