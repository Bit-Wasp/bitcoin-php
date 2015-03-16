<?php

namespace Afk11\Bitcoin\Script\Classifier;

interface ScriptClassifierInterface
{
    /**
     * @return bool
     */
    public function isPayToPublicKeyHash();

    /**
     * @return bool
     */
    public function isPayToPublicKey();

    /**
     * @return bool
     */
    public function isPayToScriptHash();

    /**
     * @return bool
     */
    public function isMultisig();

    /**
     * @return bool
     */
    public function classify();
}
