<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Address\Address;
use Afk11\Bitcoin\Address\ScriptHashAddress;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Script\Classifier\OutputClassifier;

class OutputScriptFactory
{
    /**
     * @param ScriptInterface $script
     * @return OutputClassifier
     */
    public function classify(ScriptInterface $script)
    {
        return new OutputClassifier($script);
    }

    /**
     * @param Address $address
     * @return Script
     */
    public function payToAddress(Address $address)
    {
        return ($address instanceof ScriptHashAddress
            ? ScriptFactory::create()
                ->op('OP_HASH160')
                ->push($address->getHash())
                ->op('OP_EQUAL')
            : ScriptFactory::create()
                ->op('OP_DUP')
                ->op('OP_HASH160')
                ->push($address->getHash())
                ->op('OP_EQUALVERIFY')
                ->op('OP_CHECKSIG'));

    }

    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $public_key
     * @return Script
     */
    public function payToPubKey(PublicKeyInterface $public_key)
    {
        return ScriptFactory::create()
            ->push($public_key->getBuffer())
            ->op('OP_CHECKSIG');
    }

    /**
     * Create a P2PKH output script
     *
     * @param PublicKeyInterface $public_key
     * @return Script
     */
    public function payToPubKeyHash(PublicKeyInterface $public_key)
    {
        return ScriptFactory::create()
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
    public function payToScriptHash(ScriptInterface $script)
    {
        return ScriptFactory::create()
            ->op('OP_HASH160')
            ->push($script->getScriptHash())
            ->op('OP_EQUAL');
    }
}
