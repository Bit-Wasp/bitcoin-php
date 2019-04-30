<?php

declare(strict_types=1);

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
     * @throws \Exception
     */
    public function serialize(OutPointInterface $outpoint): BufferInterface
    {
        return new Buffer(
            $this->txid->write($outpoint->getTxId()) .
            $this->vout->write($outpoint->getVout())
        );
    }

    /**
     * @param Parser $parser
     * @return OutPointInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser): OutPointInterface
    {
        return new OutPoint(
            new Buffer(strrev($parser->readBytes(32)->getBinary()), 32),
            unpack("V", $parser->readBytes(4)->getBinary())[1]
        );
    }

    /**
     * @param BufferInterface $data
     * @return OutPointInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse(BufferInterface $data): OutPointInterface
    {
        return $this->fromParser(new Parser($data));
    }
}
