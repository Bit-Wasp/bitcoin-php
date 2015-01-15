<?php

namespace Bitcoin\Signature;

use Bitcoin\Util\Buffer;
use Bitcoin\Key\PublicKeyInterface;
use Bitcoin\Script\ScriptInterface;
use Bitcoin\Transaction\TransactionOutputInterface;

/**
 * Class SignatureContainer
 * @package Bitcoin\Signature
 * @author Thomas Kerin
 */
class SignatureContainer
{
    /**
     * @var array
     */
    protected $signatures = array();

    public function __construct()
    {
        return $this;
    }

    /**
     * Add a signature to this input
     *
     * @param Signature $signature
     * @return $this
     */
    public function add(Signature $signature)
    {
        $this->signatures[] = $signature;
        return $this;
    }

    /**
     * Find a signature which is validated by the given public key
     *
     * @param Buffer $messageHash
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function find(Buffer $messageHash, PublicKeyInterface $publicKey)
    {
        foreach ($this->signatures as $sig) {
            if ($publicKey->verify($messageHash, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialize from an input script.
     *
     * @param ScriptInterface $script
     * @return $this
     */
    public function fromTxInScript(ScriptInterface $script)
    {
        $parsed = $script->parse();

        foreach ($parsed as $data) {
            try {
                $signature = Signature::fromHex($data->serialize('hex'));
                $this->add($signature);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $this;
    }

    /**
     * Serialize the signatures based on the type of output?
     * Todo:
     * @param TransactionOutputInterface $output
     */
    public function scriptFromTxOut(TransactionOutputInterface $output)
    {

    }
}
