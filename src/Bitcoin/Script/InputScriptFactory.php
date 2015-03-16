<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Script\Classifier\InputClassifier;
use Afk11\Bitcoin\Signature\SignatureCollection;
use Afk11\Bitcoin\Signature\SignatureInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Signature\Signer;

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
        $signer = new Signer(Bitcoin::getMath(), Bitcoin::getGenerator());
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
