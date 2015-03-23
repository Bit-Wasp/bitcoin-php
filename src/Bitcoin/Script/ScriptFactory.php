<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Key\KeyInterface;

class ScriptFactory
{
    /**
     * @param Buffer $script
     * @return Script
     */
    public static function create(Buffer $script = null)
    {
        return new Script($script);
    }

    /**
     * @param                   $m
     * @param KeyInterface[]    $keys
     * @param bool              $sort
     * @return Script
     */
    public static function multisig($m, array $keys = array(), $sort = true)
    {
        if ($sort) {
            usort($keys, function (KeyInterface $a, KeyInterface $b) {
                $av = (string)$a;
                $bv = (string)$b;

                return $av == $bv ? 0 : $av > $bv ? 1 : -1;
            });
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
