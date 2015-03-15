<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Signature\SignatureInterface;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Signature\Signer;

class InputScriptFactory
{
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
     * @param array $sigs
     * @param Buffer $hash
     * @return array
     */
    public function multisigP2sh(RedeemScript $redeemScript, array $sigs, Buffer $hash)
    {
        $signer = new Signer(Bitcoin::getMath(), Bitcoin::getGenerator());
        $copy = $sigs;
        $linked = array();
        $ordered = array();
        do {
            $sig = array_pop($copy);

            foreach ($redeemScript->getKeys() as $key) {
                if ($signer->verify($key, $hash, $sig)) {
                    $linked[$key->getPubKeyHash()] = $sig;
                    break;
                }
            }
        } while(count($copy) > 0);

        foreach ($redeemScript->getKeys() as $key) {
            $ordered[] = $linked[$key->getPubKeyHash()];
            unset($linked[$key->getPubKeyHash()]);
        }

        return $ordered;
    }

    public function payToPubKey(SignatureInterface $signature)
    {

    }
}
