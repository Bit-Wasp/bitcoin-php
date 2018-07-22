<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Exceptions\DisabledGlobalNetworkException;

class DisabledGlobalNetwork implements NetworkInterface
{
    public function getAddressByte()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getHDPrivByte()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getHDPubByte()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getNetMagicBytes()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getP2shByte()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getPrivByte()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getSegwitBech32Prefix()
    {
        throw new DisabledGlobalNetworkException();
    }
    public function getSignedMessageMagic()
    {
        throw new DisabledGlobalNetworkException();
    }
}
