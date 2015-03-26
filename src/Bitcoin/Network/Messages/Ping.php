<?php

namespace BitWasp\Bitcoin\Network\Messages;


use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Buffer;

class Ping extends NetworkSerializable
{
    /**
     * @var Buffer
     */
    protected $nonce;

    /**
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function __construct()
    {
        $random = new Random();
        $this->nonce = $random->bytes(8);
    }

    /**
     * @return string
     */
    public function getNetworkCommand()
    {
        return 'ping';
    }

    /**
     * @return Buffer
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return Pong
     */
    public function reply()
    {
        return new Pong($this);
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser->writeBytes(8, $this->nonce);
        return $parser->getBuffer();
    }
}