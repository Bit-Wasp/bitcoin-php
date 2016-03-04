<?php

namespace BitWasp\Bitcoin\Script\Factory;

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
        $classifier = ScriptFactory::scriptPubKey()->classify($script);

        if ($classifier->isMultisig()) {
            $handler = new Multisig($script);
        } elseif ($classifier->isPayToPublicKey()) {
            $handler = new PayToPubkey($script);
        } elseif ($classifier->isPayToPublicKeyHash()) {
            $handler = new PayToPubkeyHash($script);
        } else {
            throw new \InvalidArgumentException('Unparsable script type');
        }

        return $handler;
    }
}
