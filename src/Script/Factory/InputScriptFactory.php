<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;

class InputScriptFactory
{
    /**
     * @param ScriptInterface $script
     * @return InputClassifier
     */
    public function classify(ScriptInterface $script)
    {
        return new InputClassifier($script);
    }

    /**
     * @param \BitWasp\Bitcoin\Signature\TransactionSignatureInterface $signature
     * @param PublicKeyInterface $publicKey
     * @return ScriptInterface
     */
    public function payToPubKeyHash(TransactionSignatureInterface $signature, PublicKeyInterface $publicKey)
    {
        return ScriptFactory::create()
            ->push($signature->getBuffer())
            ->push($publicKey->getBuffer())
            ->getScript();
    }

    /**
     * @param TransactionSignatureInterface[] $signatures
     * @return ScriptInterface
     */
    public function multisig(array $signatures)
    {
        $script = ScriptFactory::create()->op('OP_0');
        foreach ($signatures as $signature) {
            $script->push($signature->getBuffer());
        }

        return $script->getScript();
    }

    /**
     * @param ScriptInterface $redeemScript
     * @param TransactionSignatureInterface[] $signatures
     * @return ScriptInterface
     */
    public function multisigP2sh(ScriptInterface $redeemScript, $signatures)
    {
        $script = ScriptFactory::create()->op('OP_0');
        foreach ($signatures as $signature) {
            $script->push($signature->getBuffer());
        }
        $script->push($redeemScript->getBuffer());

        return $script->getScript();
    }

    /**
     * @param TransactionSignatureInterface $signature
     * @return ScriptInterface
     */
    public function payToPubKey(TransactionSignatureInterface $signature)
    {
        return ScriptFactory::create()
            ->push($signature->getBuffer())
            ->getScript();
    }
}
