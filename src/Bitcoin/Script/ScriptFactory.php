<?php

namespace Afk11\Bitcoin\Script;

class ScriptFactory
{
    /**
     * @return Script
     */
    public static function create()
    {
        return new Script();
    }

    /**
     * @param $string
     * @return Script
     */
    public static function fromHex($string)
    {
        return self::create()
            ->set($string);
    }

    /**
     * @return ScriptStack
     */
    public static function stack()
    {
        return new ScriptStack();
    }

    /**
     * @return OutputScriptFactory
     */
    public static function scriptPubKey()
    {
        return new OutputScriptFactory();
    }

    /**
     * @return InputScriptFactory
     */
    public static function scriptSig()
    {
        return new InputScriptFactory();
    }
}
