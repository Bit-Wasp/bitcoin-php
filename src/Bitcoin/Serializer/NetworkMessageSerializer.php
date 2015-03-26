<?php

namespace BitWasp\Bitcoin\Serializer;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Network\NetworkMessageInterface;
use BitWasp\Bitcoin\Crypto\Hash;

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
     * @param NetworkMessageInterface $object
     * @return Buffer
     */
    public function serialize(NetworkMessageInterface $object)
    {
        $payload = $object->getBuffer();
        $payloadLength = $payload->getSize();
        $checksum = new Buffer(substr(Hash::sha256d($payload->getBinary(), true), 0, 4));

        $parser = new Parser();
        $parser
            ->writeBytes(4, $this->network->getNetMagicBytes(), true)
            ->writeBytes(12, $object->getNetworkCommand())
            ->writeInt(4, $payload->getSize())
            ->writeBytes(4, $checksum)
            ->writeBytes($payloadLength, $payload);

        return $parser->getBuffer();
    }

    /**
     * @param Parser $parser
     */
    public function fromParser(Parser & $parser)
    {

    }

    public function parse($string)
    {

    }
}
