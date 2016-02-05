<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

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
     * @return ScriptInterface
     */
    public function payToAddress(AddressInterface $address)
    {
        $script = ($address instanceof ScriptHashAddress
            ? ScriptFactory::create()
                ->op('OP_HASH160')
                ->push(Buffer::hex($address->getHash(), 20))
                ->op('OP_EQUAL')
            : ScriptFactory::create()
                ->op('OP_DUP')
                ->op('OP_HASH160')
                ->push(Buffer::hex($address->getHash(), 20))
                ->op('OP_EQUALVERIFY')
                ->op('OP_CHECKSIG'));

        return $script->getScript();
    }

    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface  $public_key
     * @return ScriptInterface
     */
    public function payToPubKey(PublicKeyInterface $public_key)
    {
        return ScriptFactory::create()
            ->push($public_key->getBuffer())
            ->op('OP_CHECKSIG')
            ->getScript();
    }

    /**
     * Create a P2PKH output script
     *
     * @param PublicKeyInterface $public_key
     * @return ScriptInterface
     */
    public function payToPubKeyHash(PublicKeyInterface $public_key)
    {
        return ScriptFactory::create()
            ->op('OP_DUP')
            ->op('OP_HASH160')
            ->push($public_key->getPubKeyHash())
            ->op('OP_EQUALVERIFY')
            ->op('OP_CHECKSIG')
            ->getScript();
    }

    /**
     * Create a P2SH output script
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptInterface
     */
    public function payToScriptHash(ScriptInterface $p2shScript)
    {
        return ScriptFactory::create()
            ->op('OP_HASH160')
            ->push($p2shScript->getScriptHash())
            ->op('OP_EQUAL')
            ->getScript();
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

        $opM = \BitWasp\Bitcoin\Script\encodeOpN($m);
        $opN = \BitWasp\Bitcoin\Script\encodeOpN($n);

        $script = ScriptFactory::create();
        foreach ($keys as $key) {
            if (!$key instanceof PublicKeyInterface) {
                throw new \LogicException('Values in $keys[] must be a PublicKey');
            }

            $script->push($key->getBuffer());
        }
        $keyBuf = $script->getScript()->getBuffer();

        $script = new Script(new Buffer(chr($opM) . $keyBuf->getBinary() . chr($opN) . chr(Opcodes::OP_CHECKMULTISIG)));
        return $script;
    }
}
