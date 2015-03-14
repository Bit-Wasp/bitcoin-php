<?php

namespace Afk11\Bitcoin\Network;

use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Parser;

class Version
{
    /**
     * @var int
     */
    public $version;

    /**
     * @var Buffer
     */
    public $services;

    /**
     *
     */
    public $timestamp;

    public $addrRecv;

    public $addrFrom;

    public $nonce;

    public $userAgent;

    public $startHeight;

    public $relay;

    public static function fromHex()
    {

    }

    public function fromParser(Parser & $parser)
    {

    }

    public function serialize($type = null)
    {
        $bytes = new Parser();
        $bytes = $bytes
            ->writeInt(4, $this->version)
            ->writeBytes(32, $this->services)
            ->writeInt(8, $this->timestamp)
            ->writeBytes(26, $this->addrRecv)
            ->writeBytes(26, $this->addrFrom)
            ->writeInt(8, $this->nonce)
            ->writeWithLength($this->userAgent)
            ->writeInt(4, $this->startHeight)
            ->writeInt(1, $this->relay)
            ->getBuffer()
            ->serialize($type);

        return $bytes;
    }
}
