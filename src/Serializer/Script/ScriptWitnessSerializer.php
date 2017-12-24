<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Script;

use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class ScriptWitnessSerializer
{
    /**
     * @var \BitWasp\Buffertools\Types\VarString
     */
    private $varstring;

    /**
     * @var \BitWasp\Buffertools\Types\VarInt
     */
    private $varint;

    public function __construct()
    {
        $this->varstring = Types::varstring();
        $this->varint = Types::varint();
    }

    /**
     * @param Parser $parser
     * @return ScriptWitnessInterface
     */
    public function fromParser(Parser $parser): ScriptWitnessInterface
    {
        $size = $this->varint->read($parser);
        $entries = [];
        for ($j = 0; $j < $size; $j++) {
            $entries[] = $this->varstring->read($parser);
        }

        return new ScriptWitness(...$entries);
    }

    /**
     * @param ScriptWitnessInterface $witness
     * @return BufferInterface
     */
    public function serialize(ScriptWitnessInterface $witness): BufferInterface
    {
        $binary = $this->varint->write($witness->count());
        foreach ($witness as $value) {
            $binary .= $this->varstring->write($value);
        }

        return new Buffer($binary);
    }
}
