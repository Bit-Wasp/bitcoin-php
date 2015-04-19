<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Buffertools\Parser;

class Pong extends NetworkSerializable
{
    /**
     * @var integer|string
     */
    protected $nonce;

    /**
     * @param Ping $ping
     */
    public function __construct(Ping $ping)
    {
        $this->nonce = $ping->getNonce();
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
        $parser = new Parser();
        $parser->writeInt(8, $this->nonce);
        return $parser->getBuffer();
    }
}
