<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 17/01/15
 * Time: 05:34
 */

namespace Bitcoin\Network;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;

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

    public function fromParser(Parser &$parser)
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
