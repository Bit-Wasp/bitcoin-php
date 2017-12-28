<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\ConsensusInterface;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptCreator;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private static $outputScriptFactory = null;

    /**
     * @param string $string
     * @return ScriptInterface
     * @throws \Exception
     */
    public static function fromHex(string $string): ScriptInterface
    {
        return self::fromBuffer(Buffer::hex($string));
    }

    /**
     * @param BufferInterface $buffer
     * @param Opcodes|null $opcodes
     * @param Math|null $math
     * @return ScriptInterface
     */
    public static function fromBuffer(BufferInterface $buffer, Opcodes $opcodes = null, Math $math = null): ScriptInterface
    {
        return self::create($buffer, $opcodes, $math)->getScript();
    }

    /**
     * @param BufferInterface|null $buffer
     * @param Opcodes|null $opcodes
     * @param Math|null $math
     * @return ScriptCreator
     */
    public static function create(BufferInterface $buffer = null, Opcodes $opcodes = null, Math $math = null): ScriptCreator
    {
        return new ScriptCreator($math ?: Bitcoin::getMath(), $opcodes ?: new Opcodes(), $buffer);
    }

    /**
     * Create a script consisting only of push-data operations.
     * Suitable for a scriptSig.
     *
     * @param BufferInterface[] $buffers
     * @return ScriptInterface
     */
    public static function pushAll(array $buffers): ScriptInterface
    {
        return self::sequence(array_map(function ($buffer) {
            if (!($buffer instanceof BufferInterface)) {
                throw new \RuntimeException('Script contained a non-push opcode');
            }

            $size = $buffer->getSize();
            if ($size === 0) {
                return Opcodes::OP_0;
            }

            $first = ord($buffer->getBinary()[0]);
            if ($size === 1 && $first >= 1 && $first <= 16) {
                return \BitWasp\Bitcoin\Script\encodeOpN($first);
            } else {
                return $buffer;
            }
        }, $buffers));
    }

    /**
     * @param int[]|\BitWasp\Bitcoin\Script\Interpreter\Number[]|BufferInterface[] $sequence
     * @return ScriptInterface
     */
    public static function sequence(array $sequence): ScriptInterface
    {
        return self::create()->sequence($sequence)->getScript();
    }

    /**
     * @param Operation[] $operations
     * @return ScriptInterface
     */
    public static function fromOperations(array $operations): ScriptInterface
    {
        $sequence = [];
        foreach ($operations as $operation) {
            if (!($operation instanceof Operation)) {
                throw new \RuntimeException("Invalid input to fromOperations");
            }

            $sequence[] = $operation->encode();
        }

        return self::sequence($sequence);
    }

    /**
     * @return OutputScriptFactory
     */
    public static function scriptPubKey(): OutputScriptFactory
    {
        if (self::$outputScriptFactory === null) {
            self::$outputScriptFactory = new OutputScriptFactory();
        }

        return self::$outputScriptFactory;
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return NativeConsensus
     */
    public static function getNativeConsensus(EcAdapterInterface $ecAdapter = null): NativeConsensus
    {
        return new NativeConsensus($ecAdapter ?: Bitcoin::getEcAdapter());
    }

    /**
     * @return BitcoinConsensus
     */
    public static function getBitcoinConsensus(): BitcoinConsensus
    {
        return new BitcoinConsensus();
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return ConsensusInterface
     */
    public static function consensus(EcAdapterInterface $ecAdapter = null): ConsensusInterface
    {
        if (extension_loaded('bitcoinconsensus')) {
            return self::getBitcoinConsensus();
        } else {
            return self::getNativeConsensus($ecAdapter);
        }
    }
}
