<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\RedeemScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
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
     * @param \BitWasp\Bitcoin\Signature\TransactionSignature $signature
     * @param PublicKeyInterface $publicKey
     * @return Script
     */
    public function payToPubKeyHash(TransactionSignature $signature, PublicKeyInterface $publicKey)
    {
        return ScriptFactory::create()
            ->push($signature->getBuffer())
            ->push($publicKey->getBuffer());
    }

    /**
     * @param RedeemScript $redeemScript
     * @param TransactionSignature[] $signatures
     * @return Script
     */
    public function multisigP2sh(RedeemScript $redeemScript, $signatures)
    {
        $script = ScriptFactory::create()->op('OP_0');

        foreach ($signatures as $signature) {
            $script->push($signature->getBuffer());
        }

        $script->push($redeemScript->getBuffer());

        return $script;
    }

    /**
     * @param TransactionSignatureInterface $signature
     * @return Script
     */
    public function payToPubKey(TransactionSignatureInterface $signature)
    {
        return ScriptFactory::create()->push($signature->getBuffer());
    }
}
