<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Serializable;
use BitWasp\Bitcoin\Serializer\NetworkMessageSerializer;

class NetworkMessage extends Serializable
{
    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * @var NetworkSerializableInterface
     */
    protected $payload;

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
        return Hash::sha256d($data, true)->slice(0, 4);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $serializer = new NetworkMessageSerializer($this->network);
        $buffer = $serializer->serialize($this);
        return $buffer;
    }
}
