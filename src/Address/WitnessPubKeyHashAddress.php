<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class WitnessPubKeyHashAddress implements AddressInterface
{
    /**
     * @var int
     */
    private $witnessVersion;

    /**
     * @var BufferInterface
     */
    private $hash;

    /**
     * WitnessAddress constructor.
     * @param int $witnessVersion
     * @param BufferInterface $hash
     */
    public function __construct($witnessVersion, BufferInterface $hash)
    {
        if (!is_int($witnessVersion)) {
            throw new \RuntimeException('Witness version must be an integer');
        }

        if ($hash->getSize() !== 20) {
            throw new \RuntimeException('Hash for P2WPKH address must be 20 bytes');
        }

        $this->witnessVersion = $witnessVersion;
        $this->hash = $hash->getHex();
    }

    /**
     * @return int
     */
    public function getWitnessVersion()
    {
        return $this->witnessVersion;
    }

    /**
     * @return BufferInterface|string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function getPrefixByte(NetworkInterface $network)
    {
        return $network->getP2WPKHByte();
    }

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $witnessByte = dechex($this->witnessVersion);
        $witnessByte = strlen($witnessByte) % 2 == 0 ? $witnessByte : '0' . $witnessByte;

        $payload = Buffer::hex($this->getPrefixByte($network) . $witnessByte . "00" . $this->getHash());
        return Base58::encodeCheck($payload);
    }
}
