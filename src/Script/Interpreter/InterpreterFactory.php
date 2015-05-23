<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Script\Interpreter\BitcoinConsensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Interpreter\Native\NativeInterpreter;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class InterpreterFactory
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ec
     */
    public function __construct(EcAdapterInterface $ec = null)
    {
        $this->ecAdapter = $ec ?: Bitcoin::getEcAdapter();
    }

    /**
     * @param $int
     * @return Flags
     */
    public function flags($int)
    {
        return new Flags($int);
    }

    /**
     * @return Flags
     */
    public function defaultFlags()
    {
        return $this->flags(
            InterpreterInterface::VERIFY_P2SH | InterpreterInterface::VERIFY_STRICTENC | InterpreterInterface::VERIFY_DERSIG |
            InterpreterInterface::VERIFY_LOW_S | InterpreterInterface::VERIFY_NULL_DUMMY | InterpreterInterface::VERIFY_SIGPUSHONLY |
            InterpreterInterface::VERIFY_DISCOURAGE_UPGRADABLE_NOPS | InterpreterInterface::VERIFY_CLEAN_STACK
        );
    }

    /**
     * @param $tx
     * @param $flags
     * @return NativeInterpreter
     */
    public function getNativeInterpreter(TransactionInterface $tx, Flags $flags)
    {
        return new NativeInterpreter($this->ecAdapter, $tx, $flags);
    }

    /**
     * @param TransactionInterface $tx
     * @param Flags $flags
     * @return BitcoinConsensus
     */
    public function getBitcoinConsensus(TransactionInterface $tx, Flags $flags)
    {
        return new BitcoinConsensus($tx, $flags);
    }

    /**
     * @param TransactionInterface $tx
     * @return BitcoinConsensus|NativeInterpreter
     */
    public function create(TransactionInterface $tx, Flags $flags)
    {
        return extension_loaded('bitcoinconsensus')
            ? $this->getBitcoinConsensus($tx, $flags)
            : $this->getNativeInterpreter($tx, $flags);
    }
}
