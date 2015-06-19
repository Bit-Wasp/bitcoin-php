<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Serializer\Network\Message\PongSerializer;
use BitWasp\Buffertools\Parser;

class Pong extends NetworkSerializable
{
    /**
     * @var integer|string
     */
    private $nonce;

    /**
     * @param int|string $nonce
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
        return 'pong';
    }

    /**
     * @return int
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return \BitWasp\Buffertools\Buffer
     */
    public function getBuffer()
    {
        return (new PongSerializer())->serialize($this);
    }
}
