<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Serializer\Network\Message\AddrSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressTimestampSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use InvalidArgumentException;

class Addr extends NetworkSerializable implements \Countable
{
    /**
     * @var NetworkAddressTimestamp[]
     */
    private $addresses = [];

    /**
     * @param NetworkAddressTimestamp[] $addresses
     */
    public function __construct(array $addresses = [])
    {
        foreach ($addresses as $addr) {
            $this->addAddress($addr);
        }
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'addr';
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->addresses);
    }

    /**
     * @return NetworkAddressTimestamp[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param int $index
     * @return NetworkAddressTimestamp
     */
    public function getAddress($index)
    {
        if (false === isset($this->addresses[$index])) {
            throw new InvalidArgumentException('No address exists at this index');
        }

        return $this->addresses[$index];
    }

    /**
     * @param NetworkAddressTimestamp $address
     * @return $this
     */
    public function addAddress(NetworkAddressTimestamp $address)
    {
        $this->addresses[] = $address;
        return $this;
    }

    /**
     * @see \BitWasp\Bitcoin\SerializableInterface::getBuffer()
     */
    public function getBuffer()
    {
        return (new AddrSerializer(new NetworkAddressTimestampSerializer()))->serialize($this);
    }
}
