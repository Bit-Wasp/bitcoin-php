<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Signature\SignatureSort;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class TxSigCreator
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
     * @var int
     */
    private $nInput;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $requiredSigs = 1;

    /**
     * TxSigCreator constructor.
     * @param EcAdapterInterface $adapter
     * @param TransactionInterface $tx
     * @param int $inputToSign
     * @param int $amount
     */
    public function __construct(EcAdapterInterface $adapter, TransactionInterface $tx, $inputToSign, $amount)
    {
        if (!isset($tx->getInputs()[$inputToSign])) {
            throw new \InvalidArgumentException('Input does not exist in transaction');
        }

        $this->ecAdapter = $adapter;
        $this->tx = $tx;
        $this->nInput = $inputToSign;
        $this->amount = $amount;
    }

    /**
     * @param PrivateKeyInterface $privKey
     * @param ScriptInterface $scriptPubKey
     * @param int $sigHashType
     * @param int $sigVersion
     * @return TransactionSignature
     */
    public function makeSignature(PrivateKeyInterface $privKey, ScriptInterface $scriptPubKey, $sigHashType, $sigVersion)
    {
        if ($sigVersion == 1) {
            $hasher = new V1Hasher($this->tx, $this->amount);
        } else {
            $hasher = new Hasher($this->tx);
        }

        $hash = $hasher->calculate($scriptPubKey, $this->nInput, $sigHashType);
        return new TransactionSignature(
            $this->ecAdapter,
            $this->ecAdapter->sign(
                $hash,
                $privKey,
                new Rfc6979(
                    $this->ecAdapter,
                    $privKey,
                    $hash,
                    'sha256'
                )
            ),
            $sigHashType
        );

    }

    /**
     * @param SignatureData $sigData
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface|null $redeemScript
     * @return $this
     */
    public function extractSignatures(SignatureData $sigData, ScriptInterface $scriptSig, ScriptInterface $redeemScript = null)
    {
        $parsed = $scriptSig->getScriptParser()->decode();
        $size = count($parsed);
        $witnessCount = isset($this->tx->getWitnesses()[$this->nInput]) ? count($this->tx->getWitnesses()[$this->nInput]) : 0;

        $sigData->signatures = [];
        switch ($sigData->innerScriptType) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                // Supply signature and public key in scriptSig
                if ($size === 2) {
                    $sigData->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getData(), $this->ecAdapter)->getBuffer()];
                    $sigData->publicKeys = [PublicKeyFactory::fromHex($parsed[1]->getData(), $this->ecAdapter)];
                }
                break;
            case OutputClassifier::PAYTOPUBKEY:
                // Only has a signature in the scriptSig
                if ($size === 1) {
                    $sigData->signatures = [TransactionSignatureFactory::fromHex($parsed[0]->getData(), $this->ecAdapter)->getBuffer()];
                }

                break;
            case OutputClassifier::MULTISIG:
                $info = new Multisig($redeemScript);
                $keyCount = $info->getKeyCount();
                $this->requiredSigs = $info->getRequiredSigCount();
                $sigData->publicKeys = $info->getKeys();
                $sigData->p2shScript = $redeemScript;
                if ($size > 2 && $size <= $keyCount + 2) {
                    $sigHash = $this->tx->getSignatureHash();
                    $sigSort = new SignatureSort($this->ecAdapter);
                    $sigs = new \SplObjectStorage;

                    foreach (array_slice($parsed, 1, -1) as $item) {
                        /** @var \BitWasp\Bitcoin\Script\Parser\Operation $item */
                        if ($item->isPush()) {
                            $txSig = TransactionSignatureFactory::fromHex($item->getData(), $this->ecAdapter);
                            $hash = $sigHash->calculate($redeemScript, $this->nInput, $txSig->getHashType());
                            $linked = $sigSort->link([$txSig->getSignature()], $sigData->publicKeys, $hash);

                            foreach ($sigData->publicKeys as $key) {
                                if ($linked->contains($key)) {
                                    $sigs[$key] = $txSig->getBuffer();
                                }
                            }
                        }
                    }

                    // We have all the signatures from the input now. array_shift the sigs for a public key, as it's encountered.
                    foreach ($sigData->publicKeys as $idx => $key) {
                        $sigData->signatures[$idx] = isset($sigs[$key]) ? $sigs[$key]->getBuffer() : null;
                    }
                }

                break;
            case OutputClassifier::WITNESS_V0_KEYHASH:
                if ($witnessCount === 2) {
                    $witness = $this->tx->getWitness($this->nInput);
                    $sigData->signatures = [TransactionSignatureFactory::fromHex($witness[0]->getData(), $this->ecAdapter)->getBuffer()];
                    $sigData->publicKeys = [PublicKeyFactory::fromHex($witness[1]->getData(), $this->ecAdapter)];
                    $sigData->p2shScript = $redeemScript;
                }
        }

        return $this;
    }

    private function signStep($txoType, SignatureData $sigData, PrivateKeyInterface $privateKey, ScriptInterface $scriptPubKey, $sigVersion, $sigHashType)
    {
        if (count($sigData->signatures) < $this->requiredSigs) {
            if ($txoType === OutputClassifier::MULTISIG) {
                foreach ($sigData->publicKeys as $keyIdx => $publicKey) {
                    if ($publicKey->getBinary() == $privateKey->getPublicKey()->getBinary()) {
                        $sigData->signatures[$keyIdx] = $this->makeSignature($privateKey, $scriptPubKey, $sigHashType, $sigVersion);
                    }
                }

                return true;
            }

            if ($txoType === OutputClassifier::PAYTOPUBKEY) {
                $publicKey = PublicKeyFactory::fromHex($sigData->solution[0]);
                if ($publicKey !== $privateKey->getPublicKey()) {
                    return false;
                }
            }

            if ($txoType === OutputClassifier::PAYTOPUBKEYHASH) {
                $publicKey = $privateKey->getPublicKey();
                if ($publicKey->getPubKeyHash()->getBinary() != $sigData->solution->getBinary()) {
                    return false;
                }

                $sigData->publicKeys[0] = $publicKey;
            }

            if ($sigData->scriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
                $publicKey = $privateKey->getPublicKey();
                if ($publicKey->getPubKeyHash()->getBinary() !== $sigData->solution->getBinary()) {
                    return false;
                }

                $scriptPubKey = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $sigData->solution, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
                $sigData->publicKeys[0] = $publicKey;
            }

            echo "Sighashtype: $sigHashType \n";
            $sigData->signatures[0] = $this->makeSignature($privateKey, $scriptPubKey, $sigHashType, $sigVersion)->getBuffer();
        }

        return true;
    }

    /**
     * @param SignatureData $sigData
     * @param PrivateKeyInterface $privateKey
     * @param ScriptInterface $scriptPubKey
     * @param int $sigHashType
     * @return $this|bool
     */
    public function signInput(SignatureData $sigData, PrivateKeyInterface $privateKey, ScriptInterface $scriptPubKey, $sigHashType)
    {
        if ($sigData->scriptType !== OutputClassifier::UNKNOWN) {
            $scriptType = $sigData->scriptType;
            $sigVersion = 0;
            if ($scriptType === OutputClassifier::PAYTOSCRIPTHASH) {
                $scriptType = $sigData->innerScriptType;
            }

            if ($scriptType === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
                echo "script hashv0000\n";
                $sigVersion = 1;
                $scriptType = (new OutputClassifier($sigData->witnessScript))->classify();
                echo "Setting it to: " . $scriptType . "\n";
            }

            if ($scriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
                $sigVersion = 1;
            }

            if ($scriptType === OutputClassifier::MULTISIG ||
                $scriptType === OutputClassifier::PAYTOPUBKEY ||
                $scriptType === OutputClassifier::PAYTOPUBKEYHASH ||
                $scriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
                echo "signing....\n";
                echo "the type is $scriptType\n";
                $this->signStep($scriptType, $sigData, $privateKey, $scriptPubKey, $sigVersion, $sigHashType);
            }
        }

        return true;
    }

    /**
     * @param SignatureData $sigData
     * @return ScriptInterface
     */
    private function makeScript($type, SignatureData $sigData)
    {
        echo "Make script for $type\n";
        $script = ScriptFactory::create();

        if (count($sigData->signatures) === $this->requiredSigs) {
            switch ($type) {
                case OutputClassifier::PAYTOPUBKEY:
                    $script->push($sigData->signatures[0]);
                    break;
                case OutputClassifier::PAYTOPUBKEYHASH:
                    $script->sequence([$sigData->signatures[0], $sigData->publicKeys[0]->getBuffer()]);
                    break;
                case OutputClassifier::MULTISIG:
                    $sequence = [Opcodes::OP_0];
                    foreach ($sigData->signatures as $sig) {
                        $sequence[] = $sig->getBuffer();
                    }

                    $script->sequence($sequence);
                    break;
            }
        }

        return $script->getScript();
    }

    public function serializeSig(SignatureData $sigData, & $scriptWitness = null)
    {
        echo "call serialize\n";
        $script = $this->makeScript($sigData->scriptType, $sigData);

        if ($sigData->scriptType === OutputClassifier::PAYTOSCRIPTHASH) {
            $sig = $script->getBuffer();
            $pushScript = $sigData->p2shScript;
            if ($sigData->innerScriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
                $sig = new Buffer();
            }

            if ($sigData->innerScriptType === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
                $sig = new Buffer();
            }

            $script = ScriptFactory::create($sig)
                ->push($pushScript->getBuffer())
                ->getScript();
        }

        if ($sigData->innerScriptType === OutputClassifier::WITNESS_V0_KEYHASH) {
            $script = new Script(); // Required, otherwise we introduce malleability.
            $values = $sigData->signatures;
            $values[] = $sigData->publicKeys[0]->getBuffer();
            $scriptWitness = new ScriptWitness($values);
        }

        var_dump($sigData->innerScriptType);
        if ($sigData->innerScriptTypew === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            echo "Serialize script hash sig\n";
            $script = new Script(); // Required, otherwise we introduce malleability.
            $script2 = $this->makeScript((new OutputClassifier($sigData->witnessScript))->classify(), $sigData);
            echo "shoudl decode: ".$script2->getScriptParser()->getHumanReadable() . PHP_EOL;
            $values = array_map(function (Operation $o) {
                return $o->getData();
            }, $script2->getScriptParser()->decode());

            $values[] = $sigData->witnessScript->getBuffer();
            $scriptWitness = new ScriptWitness($values);


        }

        return $script;
    }
}
