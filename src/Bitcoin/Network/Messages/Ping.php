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
        $this->nonce = Buffer::hex($random->bytes(8))->getInt();
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
     * @return Buffer
     */
    public function getBuffer()
    {
        $parser = new Parser();
        $parser->writeInt(8, $this->nonce);
        return $parser->getBuffer();
    }
}
