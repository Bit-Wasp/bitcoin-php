<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
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
     * @param Flags $flags
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
