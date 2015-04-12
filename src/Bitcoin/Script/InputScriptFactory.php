<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Signature\Signature;
use BitWasp\Bitcoin\Transaction\TransactionSignature;
use BitWasp\Bitcoin\Transaction\TransactionSignatureCollection;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;

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
     * @param TransactionSignature $signature
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
     * @param Signature[] $signatures
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
     * @param SignatureInterface $signature
     * @return Script
     */
    public function payToPubKey(SignatureInterface $signature)
    {
        return ScriptFactory::create()->push($signature->getBuffer());
    }
}
