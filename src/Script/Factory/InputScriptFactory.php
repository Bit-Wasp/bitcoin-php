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
        foreach ($signatures as $sig) {
            if (!$sig instanceof TransactionSignatureInterface) {
                throw new \InvalidArgumentException('Must pass TransactionSignatureInterface[]');
            }
        }

        return ScriptFactory::create()
            ->op('OP_0')
            ->pushSerializableArray($signatures)
            ->push($redeemScript->getBuffer())
            ->getScript();
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
