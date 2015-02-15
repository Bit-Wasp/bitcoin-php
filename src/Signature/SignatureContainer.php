<?php

namespace Afk11\Bitcoin\Signature;

use Bitcoin\Buffer;
use Afk11\Bitcoin\Key\PublicKeyInterface;
use Afk11\Bitcoin\Script\ScriptInterface;
use Afk11\Bitcoin\Transaction\TransactionOutputInterface;
use Mdanter\Ecc\MathAdapterInterface;
use Mdanter\Ecc\GeneratorPoint;

class SignatureContainer
{
    /**
     * @var array
     */
    protected $signatures = array();

    /**
     * @var
     */
    protected $math;

    /**
     * @var
     */
    protected $generator;

    public function __construct(MathAdapterInterface $math, GeneratorPoint $G)
    {
        $this->math = $math;
        $this->generator = $G;
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
     * @param \Bitcoin\Buffer $messageHash
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function find(Buffer $messageHash, PublicKeyInterface $publicKey)
    {
        $signer = new Signer($this->math, $this->generator);

        foreach ($this->signatures as $signature) {
            if ($signer->verify($publicKey, $messageHash, $signature)) {
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
