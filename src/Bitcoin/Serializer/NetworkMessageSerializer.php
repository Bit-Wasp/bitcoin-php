<?php

namespace Afk11\Bitcoin\Serializer;


use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Network\NetworkInterface;
use Afk11\Bitcoin\Network\NetworkMessageInterface;
use Afk11\Bitcoin\Crypto\Hash;

class NetworkMessageSerializer {

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
        $checksum = new Buffer(substr(Hash::sha256d($object->getBuffer()->serialize(), true), 0, 4));

        $parser = new Parser();
        $parser
            ->writeBytes(4, $this->network->getNetMagicBytes(), true)
            ->writeBytes(12, '')
            ->writeInt(4, $object->getBuffer()->getSize())
            ->writeBytes(4, $checksum)
            ->writeBytes($payloadLength, $payload);

        return $parser->getBuffer();
    }

    /**
     *
     */
    public function fromParser(Parser &$parser)
    {

    }

    public function parse($string)
    {

    }
}