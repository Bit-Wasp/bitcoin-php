<?php

namespace BitWasp\Bitcoin\Block;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;

class BlockFactory
{
    /**
     * @param \BitWasp\Buffertools\BufferInterface|string $string
     * @param Math $math
     * @return BlockInterface
     */
    public static function fromHex($string, Math $math = null)
    {
        $opcodes = new Opcodes();
        return (new BlockSerializer(
            $math ?: Bitcoin::getMath(),
            new BlockHeaderSerializer(),
            new TransactionSerializer(
                new TransactionInputSerializer(new OutPointSerializer(), $opcodes),
                new TransactionOutputSerializer($opcodes),
                new ScriptWitnessSerializer()
            )
        ))
            ->parse($string);
    }
}
