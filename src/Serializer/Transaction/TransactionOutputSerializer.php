<?php

declare(strict_types=1);

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
    public function serialize(TransactionOutputInterface $output): BufferInterface
    {
        return new Buffer(
            $this->uint64le->write($output->getValue()) .
            $this->varstring->write($output->getScript()->getBuffer())
        );
    }

    /**
     * @param Parser $parser
     * @return TransactionOutputInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function fromParser(Parser $parser): TransactionOutputInterface
    {
        return new TransactionOutput(
            (int) $this->uint64le->read($parser),
            new Script($this->varstring->read($parser))
        );
    }

    /**
     * @param BufferInterface $string
     * @return TransactionOutputInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public function parse(BufferInterface $string): TransactionOutputInterface
    {
        return $this->fromParser(new Parser($string));
    }
}
