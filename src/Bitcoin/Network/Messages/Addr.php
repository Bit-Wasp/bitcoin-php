<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use InvalidArgumentException;

class Addr extends NetworkSerializable
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
            if ($addr instanceof NetworkAddressTimestamp) {
                $this->addAddress($addr);
            }
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
    public function getAddresses()
    {
        return count($this->addresses);
    }

    /**
     * @param $index
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
        $parser = new Parser();
        $parser->writeArray($this->addresses);
        return $parser->getBuffer();
    }
}
