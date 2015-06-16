<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Script\Consensus\BitcoinConsensus;
use BitWasp\Bitcoin\Script\Consensus\NativeConsensus;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterFactory;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;

class ConsensusFactory
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @param EcAdapterInterface $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
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
     * @param Flags $flags
     * @return InterpreterFactory
     */
    public function interpreterFactory(Flags $flags)
    {
        return new InterpreterFactory($this->ecAdapter, $flags);
    }

    /**
     * @param Flags $flags
     * @return NativeConsensus
     */
    public function getNativeConsensus(Flags $flags)
    {
        return new NativeConsensus($this->interpreterFactory($flags));
    }

    /**
     * @param Flags $flags
     * @return BitcoinConsensus
     */
    public function getBitcoinConsensus(Flags $flags)
    {
        return new BitcoinConsensus($flags);
    }

    /**
     * @param Flags $flags
     * @return BitcoinConsensus|NativeConsensus
     */
    public function getConsensus(Flags $flags)
    {
        if (extension_loaded('bitcoinconsensus')) {
            return $this->getBitcoinConsensus($flags);
        } else {
            return $this->getNativeConsensus($flags);
        }
    }
}
