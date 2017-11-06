<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
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
     * @var TransactionSignatureSerializer
     */
    private $sigSerializer;

    /**
     * @var PublicKeySerializerInterface
     */
    private $pubKeySerializer;

    /**
     * @var bool
     */
    private $tolerateInvalidPublicKey = false;

    /**
     * @var bool
     */
    private $redeemBitcoinCash = false;

    /**
     * @var bool
     */
    private $padUnsignedMultisigs = false;

    /**
     * @var bool
     */
    private $allowComplexScripts = false;

    /**
     * @var InputSignerInterface[]
     */
    private $signatureCreator = [];

    /**
     * TxWitnessSigner constructor.
     * @param TransactionInterface $tx
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(TransactionInterface $tx, EcAdapterInterface $ecAdapter = null)
    {
        $this->tx = $tx;
        $this->ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $this->sigSerializer = new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $this->ecAdapter));
        $this->pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $this->ecAdapter);
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function redeemBitcoinCash($setting)
    {
        if (!is_bool($setting)) {
            throw new \InvalidArgumentException("Boolean value expected");
        }

        $this->redeemBitcoinCash = $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function padUnsignedMultisigs($setting)
    {
        if (!is_bool($setting)) {
            throw new \InvalidArgumentException("Boolean value expected");
        }

        $this->padUnsignedMultisigs = $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function tolerateInvalidPublicKey($setting)
    {
        if (!is_bool($setting)) {
            throw new \InvalidArgumentException("Boolean value expected");
        }

        $this->tolerateInvalidPublicKey = $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function allowComplexScripts($setting)
    {
        if (!is_bool($setting)) {
            throw new \InvalidArgumentException("Boolean value expected");
        }

        $this->allowComplexScripts = $setting;

        return $this;
    }

    /**
     * @param int $nIn
     * @param PrivateKeyInterface $key
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     * @param int $sigHashType
     * @return $this
     */
    public function sign($nIn, PrivateKeyInterface $key, TransactionOutputInterface $txOut, SignData $signData = null, $sigHashType = SigHash::ALL)
    {
        $input = $this->input($nIn, $txOut, $signData);
        foreach ($input->getSteps() as $idx => $step) {
            $input->sign($key, $sigHashType);
        }


        return $this;
    }

    /**
     * @param int $nIn
     * @param TransactionOutputInterface $txOut
     * @param SignData|null $signData
     * @return InputSignerInterface
     */
    public function input($nIn, TransactionOutputInterface $txOut, SignData $signData = null)
    {
        if (null === $signData) {
            $signData = new SignData();
        }

        if (!isset($this->signatureCreator[$nIn])) {
            $input = (new InputSigner($this->ecAdapter, $this->tx, $nIn, $txOut, $signData, $this->sigSerializer, $this->pubKeySerializer))
                ->padUnsignedMultisigs($this->padUnsignedMultisigs)
                ->tolerateInvalidPublicKey($this->tolerateInvalidPublicKey)
                ->redeemBitcoinCash($this->redeemBitcoinCash)
                ->allowComplexScripts($this->allowComplexScripts)
                ->extract();

            $this->signatureCreator[$nIn] = $input;
        }

        return $this->signatureCreator[$nIn];
    }

    /**
     * @return TransactionInterface
     */
    public function get()
    {
        $mutable = TransactionFactory::mutate($this->tx);
        $witnesses = [];
        foreach ($mutable->inputsMutator() as $idx => $input) {
            if (isset($this->signatureCreator[$idx])) {
                $sig = $this->signatureCreator[$idx]->serializeSignatures();
                $input->script($sig->getScriptSig());
                $witnesses[$idx] = $sig->getScriptWitness();
            }
        }

        if (count($witnesses) > 0) {
            $mutable->witness($witnesses);
        }

        $new = $mutable->done();
        return $new;
    }
}
