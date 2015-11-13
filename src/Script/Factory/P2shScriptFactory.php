<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;

class P2shScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private $scriptPubKey;

    /**
     * P2shScriptFactory constructor.
     * @param OutputScriptFactory $scriptPubKey
     */
    public function __construct(OutputScriptFactory $scriptPubKey)
    {
        $this->scriptPubKey = $scriptPubKey;
    }

    /**
     * @param ScriptInterface $script
     * @return ScriptInterface
     */
    private function withOutputScript(ScriptInterface $script)
    {
        return [
            $script,
            $this->scriptPubKey->payToScriptHash($script)
        ];
    }

    /**
     * Create a multisig redeemScript and outputScript
     *
     * @param $m
     * @param array $keys
     * @param bool|true $sort
     * @return ScriptInterface
     */
    public function multisig($m, array $keys, $sort = true)
    {
        return $this->withOutputScript($this->scriptPubKey->multisig($m, $keys, $sort));
    }

    /**
     * Create a Pay to pubkey redeemScript and outputScript
     * @param PublicKeyInterface  $publicKey
     * @return ScriptInterface[]
     */
    public function payToPubKey(PublicKeyInterface $publicKey)
    {
        return $this->withOutputScript($this->scriptPubKey->payToPubKey($publicKey));
    }

    /**
     * Create a Pay to pubkey-hash redeemScript and outputScript
     * @param PublicKeyInterface  $publicKey
     * @return ScriptInterface[]
     */
    public function payToPubKeyHash(PublicKeyInterface $publicKey)
    {
        return $this->withOutputScript($this->scriptPubKey->payToPubKeyHash($publicKey));
    }
}
