<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class OutPointSerializer implements OutPointSerializerInterface
{
    /**
     * @var \BitWasp\Buffertools\Types\ByteString
     */
    private $txid;

    /**
     * @var \BitWasp\Buffertools\Types\Uint32
     */
    private $vout;

    public function __construct()
    {
        $this->txid = Types::bytestringle(32);
        $this->vout = Types::uint32le();
    }

    /**
     * @param OutPointInterface $outpoint
     * @return BufferInterface
     */
    public function serialize(OutPointInterface $outpoint)
    {
        return new Buffer(
            $this->txid->write($outpoint->getTxId()) .
            $this->vout->write($outpoint->getVout())
        );
    }

    /**
     * @param Parser $parser
     * @return OutPointInterface
     */
    public function fromParser(Parser $parser)
    {
        return new OutPoint($this->txid->read($parser), $this->vout->read($parser));
    }

    /**
     * @param string|\BitWasp\Buffertools\BufferInterface $data
     * @return OutPointInterface
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
