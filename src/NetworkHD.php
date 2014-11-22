<?php

namespace Bitcoin;

/**
 * Class NetworkHD
 * @package Bitcoin
 */
class NetworkHD extends Network
{

    /**
     * Get version bytes for XPUB key
     *
     * @return string
     * @throws \Exception
     */
    public function getHDPubByte()
    {
        if ($this->xpub_byte == null){
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
        if ($this->xpriv_byte == null){
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
};