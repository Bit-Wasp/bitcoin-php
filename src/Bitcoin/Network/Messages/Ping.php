<?php

namespace BitWasp\Bitcoin\Network\Messages;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;

class Ping extends NetworkSerializable
{
    /**
     * @var integer|string
     */
    protected $nonce;

    /**
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function __construct()
    {
        $random = new Random();
        $this->nonce = $random->bytes(8)->getInt();
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
        $parser = new Parser();
        $parser->writeInt(8, $this->nonce);
        return $parser->getBuffer();
    }
}
