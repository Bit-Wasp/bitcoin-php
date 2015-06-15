<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class InterpreterFactory
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var Flags
     */
    private $flags;

    /**
     * @param EcAdapterInterface $ec
     */
    public function __construct(EcAdapterInterface $ec, Flags $flags)
    {
        $this->ecAdapter = $ec;
        $this->flags = $flags;
    }

    /**
     * @param TransactionInterface $tx
     * @return Interpreter
     */
    public function create(TransactionInterface $tx)
    {
        return new Interpreter($this->ecAdapter, $tx, $this->flags);
    }
}
