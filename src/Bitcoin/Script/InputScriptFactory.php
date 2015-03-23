<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\Signer;

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
     * @param SignatureInterface $signature
     * @param PublicKeyInterface $publicKey
     * @return Script
     */
    public function payToPubKeyHash(SignatureInterface $signature, PublicKeyInterface $publicKey)
    {
        return ScriptFactory::create()
            ->push($signature->getBuffer())
            ->push($publicKey->getBuffer());
    }

    /**
     * @param RedeemScript $redeemScript
     * @param SignatureCollection $sigs
     * @param Buffer $hash
     * @return array
     */
    public function multisigP2sh(RedeemScript $redeemScript, SignatureCollection $sigs, Buffer $hash)
    {
        $signer = Bitcoin::getEcAdapter();
        $linked = $signer->associateSigs($sigs, $hash, $redeemScript->getKeys());
        $script = ScriptFactory::create()->op('OP_0');
        foreach ($redeemScript->getKeys() as $key) {
            if (isset($linked[$key->getPubKeyHash()])) {
                $script->push($linked[$key->getPubKeyHash()]->getBuffer());
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
