<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\Message\PingSerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;

class Ping extends NetworkSerializable
{
    /**
     * @var integer|string
     */
    private $nonce;

    /**
     * @param int $nonce
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function __construct($nonce)
    {
        $this->nonce = $nonce;
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'ping';
    }

    /**
     * @return integer|string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return (new PingSerializer())->serialize($this);
    }
}
