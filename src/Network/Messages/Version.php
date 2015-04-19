<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Parser;

class Version extends NetworkSerializable
{
    /**
     * @var int|string
     */
    protected $version;

    /**
     * @var int|string
     */
    protected $services;

    /**
     * @var int|string
     */
    protected $timestamp;

    /**
     * @var NetworkAddress
     */
    protected $addrRecv;

    /**
     * @var NetworkAddress
     */
    protected $addrFrom;

    /**
     * @var Buffer
     */
    protected $userAgent;

    /**
     * @var int|string
     */
    protected $startHeight;

    /**
     * @var bool
     */
    protected $relay;

    /**
     * @var integer|string
     */
    protected $nonce;

    /**
     * @param $version
     * @param Buffer $services
     * @param $timestamp
     * @param NetworkAddress $addrRecv
     * @param NetworkAddress $addrFrom
     * @param $userAgent
     * @param $startHeight
     * @param $relay
     */
    public function __construct(
        $version,
        Buffer $services,
        $timestamp,
        NetworkAddress $addrRecv,
        NetworkAddress $addrFrom,
        $userAgent,
        $startHeight,
        $relay
    ) {
        $random = new Random();
        $this->nonce = $random->bytes(8)->getInt();
        $this->version = $version;
        $this->services = $services->getInt();
        $this->timestamp = $timestamp;
        $this->addrRecv = $addrRecv;
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
     * @return int|string
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
        $bytes = new Parser();
        $bytes
            ->writeInt(4, $this->version, true)
            ->writeInt(8, $this->services, true)
            ->writeInt(8, $this->timestamp, true)
            ->writeBytes(26, $this->addrRecv)
            ->writeBytes(26, $this->addrFrom)
            ->writeInt(8, $this->nonce, true)
            ->writeWithLength($this->userAgent)
            ->writeInt(4, $this->startHeight, true)
            ->writeInt(1, (int)$this->relay);

        return $bytes->getBuffer();
    }
}
