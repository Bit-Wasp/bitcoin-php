<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\Structure\NetworkAddressInterface;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressTimestamp;
use BitWasp\Bitcoin\Serializer\Network\Message\VersionSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;

class Version extends NetworkSerializable
{
    /**
     * @var int|string
     */
    private $version;

    /**
     * @var Buffer
     */
    private $services;

    /**
     * @var int|string
     */
    private $timestamp;

    /**
     * @var NetworkAddressInterface
     */
    private $addrRecv;

    /**
     * @var NetworkAddressInterface
     */
    private $addrFrom;

    /**
     * @var Buffer
     */
    private $userAgent;

    /**
     * @var int|string
     */
    private $startHeight;

    /**
     * @var bool
     */
    private $relay;

    /**
     * @var integer|string
     */
    private $nonce;

    /**
     * @param int $version
     * @param Buffer $services
     * @param int $timestamp
     * @param NetworkAddressInterface $addrRecv
     * @param NetworkAddressInterface $addrFrom
     * @param int $nonce
     * @param Buffer $userAgent
     * @param int $startHeight
     * @param bool $relay
     */
    public function __construct(
        $version,
        Buffer $services,
        $timestamp,
        NetworkAddressInterface $addrRecv,
        NetworkAddressInterface $addrFrom,
        $nonce,
        Buffer $userAgent,
        $startHeight,
        $relay
    ) {

        if ($addrRecv instanceof NetworkAddressTimestamp) {
            $addrRecv = $addrRecv->withoutTimestamp();
        }
        if ($addrFrom instanceof NetworkAddressTimestamp) {
            $addrFrom = $addrFrom->withoutTimestamp();
        }

        $random = new Random();
        $this->nonce = $random->bytes(8)->getInt();
        $this->version = $version;
        $this->services = $services;
        $this->timestamp = $timestamp;
        $this->addrRecv = $addrRecv;
        $this->nonce = $nonce;
        $this->addrFrom = $addrFrom;
        $this->userAgent = $userAgent;
        $this->startHeight = $startHeight;
        if (! is_bool($relay)) {
            throw new \InvalidArgumentException('Relay must be a boolean');
        }
        $this->relay = $relay;
    }

    /**
     * {@inheritdoc}
     * @see \BitWasp\Bitcoin\Network\NetworkSerializableInterface::getNetworkCommand()
     */
    public function getNetworkCommand()
    {
        return 'version';
    }

    /**
     * @return Buffer|int|string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return int|string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return Buffer
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @return int|string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return NetworkAddress
     */
    public function getRecipientAddress()
    {
        return $this->addrRecv;
    }

    /**
     * @return NetworkAddress
     */
    public function getSenderAddress()
    {
        return $this->addrFrom;
    }

    /**
     * @return Buffer
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return int|string
     */
    public function getStartHeight()
    {
        return $this->startHeight;
    }

    /**
     * @return bool
     */
    public function getRelay()
    {
        return $this->relay;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new VersionSerializer(new NetworkAddressSerializer()))->serialize($this);
    }
}
