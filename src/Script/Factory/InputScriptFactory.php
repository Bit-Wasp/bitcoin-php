<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\Classifier\InputClassifier;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;

class InputScriptFactory
{

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
     * @param ScriptInterface $inputScript
     * @param ScriptInterface $redeemScript
     * @return ScriptInterface
     */
    public function payToScriptHash(ScriptInterface $inputScript, ScriptInterface $redeemScript)
    {
        return ScriptFactory::create($inputScript->getBuffer())
            ->push($redeemScript->getBuffer())
            ->getScript();
    }
}
