<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Signature\SignatureInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;

class InputScriptFactory
{
    /**
     * @param SignatureInterface $signature
     * @param PublicKeyInterface $publicKey
     * @return $this
     */
    public function payToPubKeyHash(SignatureInterface $signature, PublicKeyInterface $publicKey)
    {
        return ScriptFactory::create()
            ->push($signature->getBuffer())
            ->push($publicKey->getBuffer());
    }

    /**
     * @param ScriptInterface $script
     * @param SignatureInterface[] $sigs
     */
    public function multisigP2sh(ScriptInterface $script, array $sigs)
    {
    }

    public function payToPubKey(SignatureInterface $signature)
    {

    }
}
