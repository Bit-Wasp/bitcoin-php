<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;

class NetworkMessage extends Serializable
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var NetworkSerializableInterface
     */
    private $payload;

    /**
     * @param NetworkInterface $network
     * @param NetworkSerializableInterface $message
     */
    public function __construct(NetworkInterface $network, NetworkSerializableInterface $message)
    {
        $this->network = $network;
        $this->payload = $message;
    }

    /**
     * @return NetworkSerializableInterface
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->payload->getNetworkCommand();
    }

    /**
     * @return Buffer
     */
    public function getChecksum()
    {
        $data = $this->getPayload()->getBuffer();
        return Hash::sha256d($data)->slice(0, 4);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new NetworkMessageSerializer($this->network))->serialize($this);
    }
}
