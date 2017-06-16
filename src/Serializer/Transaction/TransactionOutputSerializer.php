<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class TransactionOutputSerializer
{
    /**
     * @var \BitWasp\Buffertools\Types\Uint64
     */
    private $uint64le;

    /**
     * @var \BitWasp\Buffertools\Types\VarString
     */
    private $varstring;

    public function __construct()
    {
        $this->uint64le = Types::uint64le();
        $this->varstring = Types::varstring();
    }

    /**
     * @param TransactionOutputInterface $output
     * @return BufferInterface
     */
    public function serialize(TransactionOutputInterface $output)
    {
        return new Buffer(
            $this->uint64le->write($output->getValue()) .
            $this->varstring->write($output->getScript()->getBuffer())
        );
    }

    /**
     * @param Parser $parser
     * @return TransactionOutput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser)
    {
        return new TransactionOutput(
            $this->uint64le->read($parser),
            new Script($this->varstring->read($parser))
        );
    }

    /**
     * @param string $string
     * @return TransactionOutput
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse($string)
    {
        return $this->fromParser(new Parser($string));
    }
}
