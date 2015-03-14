<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Key\PublicKeyInterface;

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
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $public_key
     * @return Script
     */
    public static function payToPubKey(PublicKeyInterface $public_key)
    {
        return self::create()
            ->push($public_key->getBuffer())
            ->op('OP_CHECKSIG');
    }

    /**
     * Create a P2PKH output script
     *
     * @param PublicKeyInterface $public_key
     * @return Script
     */
    public static function payToPubKeyHash(PublicKeyInterface $public_key)
    {
        return self::create()
            ->op('OP_DUP')
            ->op('OP_HASH160')
            ->push($public_key->getPubKeyHash())
            ->op('OP_EQUALVERIFY');
    }

    /**
     * Create a P2SH output script
     *
     * @param ScriptInterface $script
     * @return Script
     */
    public static function payToScriptHash(ScriptInterface $script)
    {
        return self::create()
            ->op('OP_HASH160')
            ->push($script->getScriptHash())
            ->op('OP_EQUAL');
    }
}
