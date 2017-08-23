<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\BufferInterface;

class PayToPubKeyHashAddress extends Base58Address
{
    /**
     * PayToPubKeyHashAddress constructor.
     * @param BufferInterface $hash
     */
    public function __construct(BufferInterface $hash)
    {
        if ($hash->getSize() !== 20) {
            throw new \RuntimeException("P2PKH address hash should be 20 bytes");
        }

        parent::__construct($hash);
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        return pack("H*", $network->getAddressByte());
    }

    /**
     * @return ScriptInterface
     */
    public function getScriptPubKey()
    {
        return ScriptFactory::scriptPubKey()->payToPubKeyHash($this->getHash());
    }
}
