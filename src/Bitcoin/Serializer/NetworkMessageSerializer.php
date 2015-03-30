<?php

namespace BitWasp\Bitcoin\Serializer;

use BitWasp\Bitcoin\Network\NetworkMessage;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\NetworkInterface;

class NetworkMessageSerializer
{
    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * @param NetworkMessage $object
     * @return Buffer
     */
    public function serialize(NetworkMessage $object)
    {
        $payload = $object->getPayload()->getBuffer();
        $command = str_pad(unpack("H*", $object->getCommand())[1], 24, '0', STR_PAD_RIGHT);

        $parser = new Parser();
        $parser
            ->writeBytes(4, $this->network->getNetMagicBytes(), true)
            ->writeBytes(12, $command)
            ->writeInt(4, $payload->getSize())
            ->writeBytes(4, $object->getChecksum())
            ->writeBytes($payload->getSize(), $payload);

        return $parser->getBuffer();
    }
}
