<?php

namespace BitWasp\Bitcoin\Network\Messages;


use BitWasp\Bitcoin\Network\NetworkSerializable;

class GetHeaders extends NetworkSerializable
{
    /**
     * @var int
     */
    private $version;

    /**
     * @var string[]
     */
    private $hashes;

    /**
     * @var int
     */
    private $hashStop;

    /**
     * @param int $version
     * @param string[] $hashes
     * @param int $hashStop
     */
    public function __construct(
        $version,
        array $hashes,
        $hashStop
    ) {
        $this->version = $version;
        $this->hashes = $hashes;
        $this->hashStop = $hashStop;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'getheaders';
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string[]
     */
    public function getHashes()
    {
        return $this->hashes;
    }

    /**
     * @return int
     */
    public function getHashStop()
    {
        return $this->hashStop;
    }

    public function getBuffer()
    {

    }
}