<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory\Checker;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Script\Interpreter\CheckerBase;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;

abstract class CheckerCreatorBase
{
    /**
     * @var EcAdapterInterface
     */
    protected $ecAdapter;

    /**
     * @var TransactionSignatureSerializer
     */
    protected $txSigSerializer;

    /**
     * @var PublicKeySerializerInterface
     */
    protected $pubKeySerializer;

    /**
     * CheckerCreator constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionSignatureSerializer $txSigSerializer
     * @param PublicKeySerializerInterface $pubKeySerializer
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        TransactionSignatureSerializer $txSigSerializer,
        PublicKeySerializerInterface $pubKeySerializer
    ) {
        $this->ecAdapter = $ecAdapter;
        $this->txSigSerializer = $txSigSerializer;
        $this->pubKeySerializer = $pubKeySerializer;
    }

    /**
     * @param TransactionInterface $tx
     * @param int $nInput
     * @param TransactionOutputInterface $txOut
     * @return CheckerBase
     */
    abstract public function create(TransactionInterface $tx, int $nInput, TransactionOutputInterface $txOut): CheckerBase;
}
