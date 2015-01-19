<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 17/01/15
 * Time: 05:34
 */

namespace Network;

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

    }
}
