<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Buffer;

class ScriptFactory
{
    /**
     * @return Script
     */
    public static function create(Buffer $script = null)
    {
        return new Script($script);
    }

    /**
     * @param $m
     * @param \BitWasp\Bitcoin\Key\KeyInterface[] $keys
     * @return Script
     */
    public static function multisig($m, array $keys = array())
    {
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
     * @param $string
     * @return Script
     */
    public static function fromHex($string)
    {
        return self::create(Buffer::hex($string));
    }

    /**
     * @return ScriptStack
     */
    public static function stack()
    {
        return new ScriptStack();
    }
}
