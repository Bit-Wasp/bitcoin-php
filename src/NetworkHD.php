<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 17:16
 */

namespace Bitcoin;

class NetworkHD extends Network {

    public function getHDPubByte()
    {
        if ($this->xpub_byte == null){
            throw new \Exception('No HD xpub byte was set');
        }
        return $this->xpub_byte;
    }

    public function setHDPubByte($byte)
    {
        if (!empty($byte) and ctype_xdigit($byte) == true) {
            $this->xpub_byte = $byte;
        }
        return $this;
    }

    public function getHDPrivByte()
    {
        if ($this->xpriv_byte == null){
            throw new \Exception('No HD xpriv byte was set');
        }
        return $this->xpriv_byte;
    }

    public function setHDPrivByte($bytes)
    {
        if (!empty($bytes) and ctype_xdigit($bytes) == true) {
            $this->xpriv_byte = $bytes;
        }
        return $this;
    }
} 