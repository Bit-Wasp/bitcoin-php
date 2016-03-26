<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;

class OutputScriptFactory
{
    /**
     * @return OutputClassifier
     */
    public function classify()
    {
        return new OutputClassifier();
    }

    /**
     * @param AddressInterface $address
     * @return ScriptInterface
     */
    public function payToAddress(AddressInterface $address)
    {
        return $address instanceof ScriptHashAddress
            ? ScriptFactory::sequence([Opcodes::OP_HASH160, Buffer::hex($address->getHash(), 20), Opcodes::OP_EQUAL])
            : ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, Buffer::hex($address->getHash(), 20), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $publicKey
     * @return ScriptInterface
     */
    public function payToPubKey(PublicKeyInterface $publicKey)
    {
        return ScriptFactory::sequence([$publicKey->getBuffer(), Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a P2PKH output script
     *
     * @param PublicKeyInterface $public_key
     * @return ScriptInterface
     */
    public function payToPubKeyHash(PublicKeyInterface $public_key)
    {
        return ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $public_key->getPubKeyHash(), Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
    }

    /**
     * Create a P2SH output script
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptInterface
     */
    public function payToScriptHash(ScriptInterface $p2shScript)
    {
        return ScriptFactory::sequence([Opcodes::OP_HASH160, $p2shScript->getScriptHash(), Opcodes::OP_EQUAL]);
    }

    /**
     * @param int $m
     * @param PublicKeyInterface[] $keys
     * @param bool|true $sort
     * @return ScriptCreator|Script
     */
    public function multisig($m, array $keys = [], $sort = true)
    {
        $n = count($keys);
        if ($m > $n) {
            throw new \LogicException('Required number of sigs exceeds number of public keys');
        }

        if ($n > 16) {
            throw new \LogicException('Number of public keys is greater than 16');
        }

        if ($sort) {
            $keys = Buffertools::sort($keys);
        }

        $new = ScriptFactory::create()->int($m);
        foreach ($keys as $key) {
            if (!$key instanceof PublicKeyInterface) {
                throw new \LogicException('Values in $keys[] must be a PublicKey');
            }

            $new->push($key->getBuffer());
        }

        $new->int($n)->op('OP_CHECKMULTISIG');

        return $new->getScript();
    }
}
