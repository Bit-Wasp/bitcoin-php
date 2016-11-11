<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;

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
     * @param AddressInterface $address
     * @return Script
     */
    public function payToAddress(AddressInterface $address)
    {
        return ($address instanceof ScriptHashAddress
            ? ScriptFactory::create()
                ->op('OP_HASH160')
                ->push(Buffer::hex($address->getHash()))
                ->op('OP_EQUAL')
            : ScriptFactory::create()
                ->op('OP_DUP')
                ->op('OP_HASH160')
                ->push(Buffer::hex($address->getHash()))
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
            ->op('OP_EQUALVERIFY')
            ->op('OP_CHECKSIG');
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

    /**
     * @param Buffer $secret
     * @param PublicKeyInterface $a1
     * @param PublicKeyInterface $a2
     * @param PublicKeyInterface $b1
     * @param PublicKeyInterface $b2
     * @return Script
     */
    public function payToLightningChannel(
        Buffer $secret,
        PublicKeyInterface $a1,
        PublicKeyInterface $a2,
        PublicKeyInterface $b1,
        PublicKeyInterface $b2
    ) {
        return ScriptFactory::create()
            ->op('OP_DEPTH')
            ->op('OP_3')
            ->op('OP_EQUAL')
            ->op('OP_IF')
                ->op('OP_HASH160')
                ->push(Hash::sha256ripe160($secret))
                ->op('OP_EQUALVERIFY')
                ->concat(ScriptFactory::multisig(2, [$a1, $b1]))
            ->op('OP_ELSE')
                ->concat(ScriptFactory::multisig(2, [$a2, $b2]))
            ->op('OP_ENDIF');
    }
}
