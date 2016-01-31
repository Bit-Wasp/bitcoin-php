<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 30/01/16
 * Time: 21:52
 */

namespace BitWasp\Bitcoin\Transaction\Factory;


use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

class TxSigning
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
     * @var SignatureData
     */
    private $signatures = [];

    /**
     * @var TxInputSigning
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
        $nInputs = count($tx->getInputs());
        for ($i = 0; $i < $nInputs; $i++) {
            $this->signatures[] = new SignatureData();
        }
    }

    /**
     * @param int $nIn
     * @param PrivateKeyInterface $key
     * @param TransactionOutputInterface $txOut
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @return int
     */
    public function sign($nIn, PrivateKeyInterface $key, TransactionOutputInterface $txOut, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null)
    {
        if (!isset($this->signatureCreator[$nIn])) {
            $this->signatureCreator[$nIn] = new TxInputSigning($this->ecAdapter, $this->tx, $nIn, $txOut);
        }

        if ($this->signatureCreator[$nIn]->sign($key, $txOut->getScript())) {
            throw new \RuntimeException('Unsignable script');
        }

        return $this->signatureCreator[$nIn]->isFullySigned();
    }

    public function get()
    {
        $mutable = TransactionFactory::mutate($this->tx);
        $witnesses = [];
        foreach ($mutable->inputsMutator() as $idx => $input) {
            $sigData = $this->signatures[$idx];
            $sigCreator = $this->signatureCreator[$idx];
            $witness = null;

            $sig = $sigCreator->serializeSig($sigData, $witness);
            echo "ScriptSig: \n";
            echo $sig->getHex() . "\n";

            echo "Witness data\n";
            var_dump($witness);
            $input->script($sig);
            $witnesses[$idx] = $witness ?: new ScriptWitness([]);
        }

        if (count($witnesses) > 0) {
            $mutable->witness(new TransactionWitnessCollection($witnesses));
        }

        $new = $mutable->done();
        return $new;
    }
}