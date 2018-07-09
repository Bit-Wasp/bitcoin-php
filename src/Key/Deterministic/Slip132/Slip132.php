<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic\Slip132;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Exceptions\NotImplementedException;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\ScriptPrefix;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;

class Slip132
{
    /**
     * @var KeyToScriptHelper
     */
    private $helper;

    /**
     * Slip132PrefixRegistry constructor.

     * @param KeyToScriptHelper $helper
     */
    public function __construct(KeyToScriptHelper $helper = null)
    {
        $this->helper = $helper ?: new KeyToScriptHelper(Bitcoin::getEcAdapter());
    }

    /**
     * @param PrefixRegistry $registry
     * @param ScriptDataFactory $factory
     * @return ScriptPrefix
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    private function loadPrefix(PrefixRegistry $registry, ScriptDataFactory $factory): ScriptPrefix
    {
        list ($private, $public) = $registry->getPrefixes($factory->getScriptType());
        return new ScriptPrefix($factory, $private, $public);
    }

    /**
     * xpub on bitcoin
     * @param PrefixRegistry $registry
     * @return ScriptPrefix
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function p2pkh(PrefixRegistry $registry): ScriptPrefix
    {
        return $this->loadPrefix($registry, $this->helper->getP2pkhFactory());
    }

    /**
     * ypub on bitcoin
     * @param PrefixRegistry $registry
     * @return ScriptPrefix
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function p2shP2wpkh(PrefixRegistry $registry): ScriptPrefix
    {
        return $this->loadPrefix($registry, $this->helper->getP2shFactory($this->helper->getP2wpkhFactory()));
    }

    /**
     * Ypub on bitcoin
     * @param PrefixRegistry $registry
     * @return ScriptPrefix
     * @throws NotImplementedException
     */
    public function p2shP2wshP2pkh(PrefixRegistry $registry): ScriptPrefix
    {
        throw new NotImplementedException("Ypub/prv not supported yet");
    }

    /**
     * zpub on bitcoin
     * @param PrefixRegistry $registry
     * @return ScriptPrefix
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
     */
    public function p2wpkh(PrefixRegistry $registry): ScriptPrefix
    {
        return $this->loadPrefix($registry, $this->helper->getP2wpkhFactory());
    }

    /**
     * Zpub on bitcoin
     * @param PrefixRegistry $registry
     * @return ScriptPrefix
     * @throws NotImplementedException
     */
    public function p2wshP2pkh(PrefixRegistry $registry): ScriptPrefix
    {
        throw new NotImplementedException("Zpub/prv not supported yet");
    }
}
