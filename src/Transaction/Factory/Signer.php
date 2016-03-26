<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class Signer
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * @var InputSigner
     */
    private $signatureCreator = [];

    /**
     * TxWitnessSigner constructor.
     * @param TransactionInterface $tx
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(TransactionInterface $tx, EcAdapterInterface $ecAdapter)
    {
        $this->tx = $tx;
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param int $nIn
     * @param PrivateKeyInterface $key
     * @param TransactionOutputInterface $txOut
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @param int $sigHashType
     * @return $this
     */
    public function sign($nIn, PrivateKeyInterface $key, TransactionOutputInterface $txOut, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null, $sigHashType = SigHashInterface::ALL)
    {
        if (!isset($this->signatureCreator[$nIn])) {
            $this->signatureCreator[$nIn] = new InputSigner($this->ecAdapter, $this->tx, $nIn, $txOut, $sigHashType);
        }

        if (!$this->signatureCreator[$nIn]->sign($key, $redeemScript, $witnessScript)) {
            throw new \RuntimeException('Unsignable script');
        }

        return $this;
    }

    /**
     * @return TransactionInterface
     */
    public function get()
    {
        $mutable = TransactionFactory::mutate($this->tx);
        $witnesses = [];
        foreach ($mutable->inputsMutator() as $idx => $input) {
            $sig = $this->signatureCreator[$idx]->serializeSignatures();
            $input->script($sig->getScriptSig());
            $witnesses[$idx] = $sig->getScriptWitness();
        }

        if (count($witnesses) > 0) {
            $mutable->witness(new TransactionWitnessCollection($witnesses));
        }

        $new = $mutable->done();
        return $new;
    }
}
