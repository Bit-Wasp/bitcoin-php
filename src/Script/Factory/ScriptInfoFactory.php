<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\ScriptHash;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptInterface;

class ScriptInfoFactory
{
    /**
     * @param ScriptInterface $script
     * @return \BitWasp\Bitcoin\Script\ScriptInfo\ScriptInfoInterface
     */
    public function load(ScriptInterface $script)
    {
        $classifier = new OutputClassifier();
        if ($classifier->isMultisig($script)) {
            $handler = new Multisig($script);
        } elseif ($classifier->isPayToPublicKey($script)) {
            $handler = new PayToPubkey($script);
        } elseif ($classifier->isPayToPublicKeyHash($script)) {
            $handler = new PayToPubkeyHash($script);
        } else {
            throw new \InvalidArgumentException('Script type is non-standard, no parser available');
        }

        return $handler;
    }
}
