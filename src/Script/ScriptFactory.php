<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptCreator;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private static $outputScriptFactory = null;

    /**
     * @param BufferInterface|string $string
     * @return ScriptInterface
     */
    public static function fromHex($string)
    {
        return self::create($string instanceof BufferInterface ? $string : Buffer::hex($string))->getScript();
    }

    /**
     * @param BufferInterface|null $buffer
     * @param Opcodes|null $opcodes
     * @param Math|null $math
     * @return ScriptCreator
     */
    public static function create(BufferInterface $buffer = null, Opcodes $opcodes = null, Math $math = null)
    {
        return new ScriptCreator($math ?: Bitcoin::getMath(), $opcodes ?: new Opcodes(), $buffer);
    }

    /**
     * @param int[]|\BitWasp\Bitcoin\Script\Interpreter\Number[]|BufferInterface[] $sequence
     * @return ScriptInterface
     */
    public static function sequence(array $sequence)
    {
        return self::create()->sequence($sequence)->getScript();
    }

    /**
     * @return OutputScriptFactory
     */
    public static function scriptPubKey()
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
    public static function getNativeConsensus(EcAdapterInterface $ecAdapter = null)
    {
        return new NativeConsensus($ecAdapter ?: Bitcoin::getEcAdapter());
    }

    /**
     * @return BitcoinConsensus
     */
    public static function getBitcoinConsensus()
    {
        return new BitcoinConsensus();
    }

    /**
     * @param EcAdapterInterface|null $ecAdapter
     * @return \BitWasp\Bitcoin\Script\Consensus\ConsensusInterface
     */
    public static function consensus(EcAdapterInterface $ecAdapter = null)
    {
        if (extension_loaded('bitcoinconsensus')) {
            return self::getBitcoinConsensus();
        } else {
            return self::getNativeConsensus($ecAdapter);
        }
    }
}
