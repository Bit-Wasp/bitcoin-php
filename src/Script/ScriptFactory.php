<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Script\Factory\InputScriptFactory;
use BitWasp\Bitcoin\Script\Factory\OutputScriptFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Key\KeyInterface;

class ScriptFactory
{
    /**
     * @param Buffer $script
     * @return Script
     */
    public static function create(Buffer $script = null)
    {
        return new Script($script ?: new Buffer());
    }

    /**
     * @param                   $m
     * @param KeyInterface[]    $keys
     * @param bool              $sort
     * @return RedeemScript
     */
    public static function multisig($m, array $keys = array(), $sort = true)
    {
        if ($sort) {
            $keys = \BitWasp\Buffertools\Buffertools::sort($keys);
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
}
