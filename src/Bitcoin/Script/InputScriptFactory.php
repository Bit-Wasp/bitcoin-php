<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
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
     * @param TransactionSignatureCollection $sigs
     * @param Buffer $hash
     * @return array
     */
    public function multisigP2sh(RedeemScript $redeemScript, TransactionSignatureCollection $sigs, Buffer $hash)
    {
        $signer = Bitcoin::getEcAdapter();

        // Extract signatures
        $signatures = new SignatureCollection(array_map(
            function (TransactionSignature $value) {
                return $value->getSignature();
            },
            $sigs->getSignatures()
        ));

        // Associate signatures with public keys
        $linked = $signer->associateSigs($signatures, $hash, $redeemScript->getKeys());

        // Create the script
        $script = ScriptFactory::create()->op('OP_0');
        foreach ($redeemScript->getKeys() as $key) {
            $keyHash = $key->getPubKeyHash()->getHex();
            if (isset($linked[$keyHash])) {
                $sig = array_shift($linked[$keyHash]);
                $script->push($sig->getBuffer());
            }
        }

        $script->push($redeemScript->getBuffer());

        return $script;
    }

    /**
     * @param SignatureInterface $signature
     * @return $this
     */
    public function payToPubKey(SignatureInterface $signature)
    {
        return ScriptFactory::create()->push($signature->getBuffer());
    }
}
