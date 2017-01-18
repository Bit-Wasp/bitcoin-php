<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class TransactionInputSerializer
{
    /**
     * @var OutPointSerializer
     */
    private $outpointSerializer;

    /**
     * TransactionInputSerializer constructor.
     * @param OutPointSerializerInterface $outPointSerializer
     */
    public function __construct(OutPointSerializerInterface $outPointSerializer)
    {
        $this->outpointSerializer = $outPointSerializer;
        $this->varstring = Types::varstring();
        $this->uint32le = Types::uint32le();
    }

    /**
     * @param TransactionInputInterface $input
     * @return BufferInterface
     */
    public function serialize(TransactionInputInterface $input)
    {
        return new Buffer(
            $this->outpointSerializer->serialize($input->getOutPoint())->getBinary() .
            $this->varstring->write($input->getScript()->getBuffer()) .
            $this->uint32le->write($input->getSequence())
        );
    }

    /**
     * @param Parser $parser
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        return new TransactionInput(
            $this->outpointSerializer->fromParser($parser),
            new Script($this->varstring->read($parser)),
            $this->uint32le->read($parser)
        );
    }

    /**
     * @param BufferInterface|string $string
     * @return TransactionInput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }
}
