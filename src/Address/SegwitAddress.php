<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\SegwitBech32;

class SegwitAddress extends Address implements Bech32AddressInterface
{
    /**
     * @var WitnessProgram
     */
    protected $witnessProgram;

    /**
     * SegwitAddress constructor.
     * @param WitnessProgram $witnessProgram
     */
    public function __construct(WitnessProgram $witnessProgram)
    {
        $this->witnessProgram = $witnessProgram;

        parent::__construct($witnessProgram->getProgram());
    }

    /**
     * @param NetworkInterface|null $network
     * @return bool
     */
    public function getHRP(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        return $network->getSegwitBech32Prefix();
    }

    /**
     * @return \BitWasp\Bitcoin\Script\ScriptInterface
     */
    public function getScriptPubKey()
    {
        return $this->witnessProgram->getScript();
    }

    /**
     * @return WitnessProgram
     */
    public function getWitnessProgram()
    {
        return $this->witnessProgram;
    }

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function getAddress(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        return SegwitBech32::encode($this->witnessProgram, $network);
    }
}
