<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Factory\InputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\ScriptInfoFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Buffertools\Buffertools;

class ScriptFactory
{
    /**
     * @param Buffer|null $buffer
     * @param Opcodes|null $opcodes
     * @param Math|null $math
     * @return ScriptCreator
     */
    public static function create(Buffer $buffer = null, Opcodes $opcodes = null, Math $math = null)
    {
        return new ScriptCreator($math ?: Bitcoin::getMath(), $opcodes ?: new Opcodes(), $buffer);
    }

    /**
     * @param int               $m
     * @param KeyInterface[]    $keys
     * @param bool              $sort
     * @return ScriptInterface
     */
    public static function multisig($m, array $keys = array(), $sort = true)
    {
        if ($sort) {
            $keys = Buffertools::sort($keys);
        }

        return self::scriptPubKey()->multisig($m, $keys);
    }

    /**
     * @param int               $m
     * @param KeyInterface[]    $keys
     * @param bool              $sort
     * @return ScriptInterface
     */
    public static function multisigNew($m, array $keys = array(), $sort = true)
    {
        if ($sort) {
            $keys = Buffertools::sort($keys);
        }

        return self::scriptPubKey()->multisig($m, $keys);
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
     * @param ScriptInterface $script
     * @param ScriptInterface|null $redeemScript
     * @return ScriptInfo\ScriptInfoInterface
     */
    public static function info(ScriptInterface $script, ScriptInterface $redeemScript = null)
    {
        return (new ScriptInfoFactory())->load($script, $redeemScript);
    }

     /**
     * @param Buffer|string $string
     * @return Script
     */
    public static function fromHex($string)
    {
        return self::create($string instanceof Buffer ? $string : Buffer::hex($string))->getScript();
    }
}
