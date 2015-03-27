<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Parser;

class Pong extends NetworkSerializable
{
    /**
     * @var \BitWasp\Bitcoin\Buffer
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

    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser->writeBytes(8, $this->nonce);
        return $parser->getBuffer();
    }
}
