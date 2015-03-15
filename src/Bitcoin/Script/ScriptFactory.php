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
     * @return Opcodes
     */
    public static function opCodes()
    {
        return new Opcodes;
    }

    /**
     * @param ScriptInterface $script
     * @return RedeemScript
     */
    public static function redeemScript(ScriptInterface $script)
    {
        return new RedeemScript($script);
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
     * @param $m
     * @param \Afk11\Bitcoin\Key\KeyInterface[] $keys
     * @return Script
     */
    public static function multisig($m, array $keys = array())
    {
        $n = count($keys);
        if ($m > $n) {
            throw new \LogicException('Required number of sigs exceeds number of public keys');
        }
        if ($n > 16) {
            throw new \LogicException('Number of public keys is greater than 16');
        }
        $script = self::create();
        $ops = $script->getOpCodes();
        $opM = $ops->getOp($ops->getOpByName('OP_1') - 1 + $m);
        $opN = $ops->getOp($ops->getOpByName('OP_1') - 1 + $n);

        $script->op($opM);
        foreach ($keys as $key) {
            $public = $key->isPrivate()
                ? $key->getPublicKey()
                : $key;

            $script->push($public->getBuffer());
        }
        $script
            ->op($opN)
            ->op('OP_CHECKMULTISIG');

        echo $script->getBuffer()."\n";
        return $script;
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
