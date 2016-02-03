<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\Factory\InputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\P2shScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptCreator;
use BitWasp\Bitcoin\Script\Factory\ScriptInfoFactory;
use BitWasp\Bitcoin\Script\Factory\WitnessScriptFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ScriptFactory
{
    /**
     * @param BufferInterface|string $string
     * @return Script
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
     * @return InputScriptFactory
     */
    public static function scriptSig()
    {
        return new InputScriptFactory();
    }

    /**
     * @return OutputScriptFactory
     */
    public static function scriptPubKey()
    {
        return new OutputScriptFactory();
    }

    /**
     * @param Opcodes|null $opcodes
     * @return P2shScriptFactory
     */
    public static function p2sh(Opcodes $opcodes = null)
    {
        return new P2shScriptFactory(self::scriptPubKey(), $opcodes ?: new Opcodes());
    }

    /**
     * @param Opcodes|null $opcodes
     * @return WitnessScriptFactory
     */
    public static function witness(Opcodes $opcodes = null)
    {
        return new WitnessScriptFactory(self::scriptPubKey(), $opcodes ?: new Opcodes());
    }

    /**
     * @param ScriptInterface $script
     * @param ScriptInterface|null $redeemScript
     * @return ScriptInfo\ScriptInfoInterface
     */
    public static function info(ScriptInterface $script, ScriptInterface $redeemScript = null)
    {
        return (new ScriptInfoFactory())->load($script, $redeemScript);
    }

    /**
     * @return Flags
     */
    public static function defaultFlags()
    {
        return new Flags(
            InterpreterInterface::VERIFY_P2SH | InterpreterInterface::VERIFY_STRICTENC | InterpreterInterface::VERIFY_DERSIG |
            InterpreterInterface::VERIFY_LOW_S | InterpreterInterface::VERIFY_NULL_DUMMY | InterpreterInterface::VERIFY_SIGPUSHONLY |
            InterpreterInterface::VERIFY_DISCOURAGE_UPGRADABLE_NOPS | InterpreterInterface::VERIFY_CLEAN_STACK |
            InterpreterInterface::VERIFY_CHECKLOCKTIMEVERIFY | InterpreterInterface::VERIFY_CHECKSEQUENCEVERIFY
        );
    }

    /**
     * @param Flags|null $flags
     * @param EcAdapterInterface|null $ecAdapter
     * @return NativeConsensus
     */
    public static function getNativeConsensus(Flags $flags = null, EcAdapterInterface $ecAdapter = null)
    {
        return new NativeConsensus($ecAdapter ?: Bitcoin::getEcAdapter(), $flags ?: self::defaultFlags());
    }

    /**
     * @param Flags|null $flags
     * @return BitcoinConsensus
     */
    public static function getBitcoinConsensus(Flags $flags = null)
    {
        return new BitcoinConsensus($flags ?: self::defaultFlags());
    }

    /**
     * @param Flags|null $flags
     * @param EcAdapterInterface|null $ecAdapter
     * @return \BitWasp\Bitcoin\Script\Consensus\ConsensusInterface
     */
    public static function consensus(Flags $flags = null, EcAdapterInterface $ecAdapter = null)
    {
        if (extension_loaded('bitcoinconsensus')) {
            return self::getBitcoinConsensus($flags);
        } else {
            return self::getNativeConsensus($flags, $ecAdapter);
        }
    }
}
