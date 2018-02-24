<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Exceptions\SignerException;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Transaction\Factory\Checker\CheckerCreator;
use BitWasp\Bitcoin\Transaction\Factory\Checker\CheckerCreatorBase;
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
     * @var InputSignerInterface[]
     */
    private $signatureCreator = [];

    /**
     * @var CheckerCreatorBase
     */
    private $checkerCreator;

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
        $this->checkerCreator = new CheckerCreator($this->ecAdapter, $this->sigSerializer, $this->pubKeySerializer);
    }

    /**
     * @param CheckerCreatorBase $checkerCreator
     * @return $this
     * @throws SignerException
     */
    public function setCheckerCreator(CheckerCreatorBase $checkerCreator)
    {
        if (empty($this->signatureCreator)) {
            $this->checkerCreator = $checkerCreator;
            return $this;
        }

        throw new SignerException("Cannot change CheckerCreator after inputs have been parsed");
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
     * @param int $nIn
     * @param PrivateKeyInterface $key
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     * @param int $sigHashType
     * @return $this
     */
    public function sign($nIn, PrivateKeyInterface $key, TransactionOutputInterface $txOut, SignData $signData = null, $sigHashType = SigHash::ALL)
    {
        if (!$this->input($nIn, $txOut, $signData)->sign($key, $sigHashType)) {
            throw new \RuntimeException('Unsignable script');
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
            $checker = $this->checkerCreator->create($this->tx, $nIn, $txOut);
            $input = (new InputSigner($this->ecAdapter, $this->tx, $nIn, $txOut, $signData, $checker, $this->sigSerializer, $this->pubKeySerializer))
                ->tolerateInvalidPublicKey($this->tolerateInvalidPublicKey)
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
