<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Factory\InputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
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
     * @param Buffer $script
     * @return Script
     */
    public static function createOld(Buffer $script = null)
    {
        return new Script($script ?: new Buffer());
    }

    /**
     * @param int               $m
     * @param KeyInterface[]    $keys
     * @param bool              $sort
     * @return RedeemScript
     */
    public static function multisig($m, array $keys = array(), $sort = true)
    {
        if ($sort) {
            $keys = Buffertools::sort($keys);
        }

        return new RedeemScript($m, $keys);
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
     * @param Buffer|string $string
     * @return Script
     */
    public static function fromHex($string)
    {
        return self::create($string instanceof Buffer ? $string : Buffer::hex($string))->getScript();
    }
}
