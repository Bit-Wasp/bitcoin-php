<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Signature\SignatureSort;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\BufferInterface;

class InputSigner
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var ScriptInterface $redeemScript
     */
    private $redeemScript;

    /**
     * @var ScriptInterface $witnessScript
     */
    private $witnessScript;

    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * @var int
     */
    private $nInput;

    /**
     * @var TransactionOutputInterface
     */
    private $txOut;

    /**
     * @var PublicKeyInterface[]
     */
    private $publicKeys = [];

    /**
     * @var int
     */
    private $sigHashType;

    /**
     * @var TransactionSignatureInterface[]
     */
    private $signatures = [];

    /**
     * @var int
     */
    private $requiredSigs = 0;

    /**
     * @var OutputClassifier
     */
    private $classifier;

    /**
     * TxInputSigning constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @param int $nInput
     * @param TransactionOutputInterface $txOut
     * @param int $sigHashType
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, $sigHashType = SigHashInterface::ALL)
    {
        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->classifier = new OutputClassifier();
        $this->sigHashType = $sigHashType;
        $this->publicKeys = [];
        $this->signatures = [];

        $this->extractSignatures();
    }

    /**
     * @param int $sigVersion
     * @param $stack
     * @param ScriptInterface $scriptCode
     * @return \SplObjectStorage
     */
    private function sortMultiSigs($sigVersion, $stack, ScriptInterface $scriptCode)
    {
        if ($sigVersion === 1) {
            $hasher = new V1Hasher($this->tx, $this->txOut->getValue());
        } else {
            $hasher = new Hasher($this->tx);
        }

        $sigSort = new SignatureSort($this->ecAdapter);
        $sigs = new \SplObjectStorage;

        foreach ($stack as $txSig) {
            $hash = $hasher->calculate($scriptCode, $this->nInput, $txSig->getHashType());
            $linked = $sigSort->link([$txSig->getSignature()], $this->publicKeys, $hash);

            foreach ($this->publicKeys as $key) {
                if ($linked->contains($key)) {
                    $sigs[$key] = $txSig;
                }
            }
        }

        return $sigs;
    }

    /**
     * @param string $type
     * @param ScriptInterface $scriptCode
     * @param BufferInterface[] $stack
     * @param int $sigVersion
     * @return string
     */
    public function extractFromValues($type, ScriptInterface $scriptCode, array $stack, $sigVersion)
    {
        $size = count($stack);
        if ($type === OutputClassifier::PAYTOPUBKEYHASH) {
            $this->requiredSigs = 1;
            if ($size === 2) {
                $this->signatures = [TransactionSignatureFactory::fromHex($stack[0], $this->ecAdapter)];
                $this->publicKeys = [PublicKeyFactory::fromHex($stack[1], $this->ecAdapter)];
            }
        }

        if ($type === OutputClassifier::PAYTOPUBKEY) {
            $this->requiredSigs = 1;
            if ($size === 1) {
                $this->signatures = [TransactionSignatureFactory::fromHex($stack[0], $this->ecAdapter)];
            }
        }

        if ($type === OutputClassifier::MULTISIG) {
            $info = new Multisig($scriptCode);
            $this->requiredSigs = $info->getRequiredSigCount();
            $this->publicKeys = $info->getKeys();

            if ($size > 1) {
                $vars = [];
                foreach (array_slice($stack, 1, -1) as $sig) {
                    $vars[] = TransactionSignatureFactory::fromHex($sig, $this->ecAdapter);
                }

                $sigs = $this->sortMultiSigs($sigVersion, $vars, $scriptCode);

                foreach ($this->publicKeys as $idx => $key) {
                    $this->signatures[$idx] = isset($sigs[$key]) ? $sigs[$key]->getBuffer() : null;
                }
            }
        }

        return $type;
    }

    /**
     * @return $this
     */
    public function extractSignatures()
    {
        $scriptPubKey = $this->txOut->getScript();
        $scriptSig = $this->tx->getInput($this->nInput)->getScript();
        $type = $this->classifier->classify($scriptPubKey);

        if ($type === OutputClassifier::PAYTOPUBKEYHASH || $type === OutputClassifier::PAYTOPUBKEY || $type === OutputClassifier::MULTISIG) {
            $values = [];
            foreach ($scriptSig->getScriptParser()->decode() as $o) {
                $values[] = $o->getData();
            }

            $this->extractFromValues($type, $scriptPubKey, $values, 0);
        }

        if ($type === OutputClassifier::PAYTOSCRIPTHASH) {
            $decodeSig = $scriptSig->getScriptParser()->decode();
            if (count($decodeSig) > 0) {
                $redeemScript = new Script(end($decodeSig)->getData());
                $p2shType = $this->classifier->classify($redeemScript);

                if (count($decodeSig) > 1) {
                    $decodeSig = array_slice($decodeSig, 0, -1);
                }

                $internalSig = [];
                foreach ($decodeSig as $operation) {
                    $internalSig[] = $operation->getData();
                }

                $this->redeemScript = $redeemScript;
                $this->extractFromValues($p2shType, $redeemScript, $internalSig, 0);

                $type = $p2shType;
            }
        }

        $witnesses = $this->tx->getWitnesses();
        if ($type === OutputClassifier::WITNESS_V0_KEYHASH) {
            $this->requiredSigs = 1;
            if (isset($witnesses[$this->nInput])) {
                $witness = $witnesses[$this->nInput];
                $this->signatures = [TransactionSignatureFactory::fromHex($witness[0], $this->ecAdapter)];
                $this->publicKeys = [PublicKeyFactory::fromHex($witness[1], $this->ecAdapter)];
            }
        } else if ($type === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if (isset($witnesses[$this->nInput])) {
                $witness = $witnesses[$this->nInput];
                $witCount = count($witnesses[$this->nInput]);
                if ($witCount > 0) {
                    $witnessScript = new Script($witness[$witCount - 1]);
                    $vWitness = $witness->all();
                    if (count($vWitness) > 1) {
                        $vWitness = array_slice($witness->all(), 0, -1);
                    }

                    $witnessType = $this->classifier->classify($witnessScript);
                    $this->extractFromValues($witnessType, $witnessScript, $vWitness, 1);
                    $this->witnessScript = $witnessScript;
                }
            }
        }

        return $this;
    }

    /**
     * @param PrivateKeyInterface $key
     * @param ScriptInterface $scriptCode
     * @param int $sigVersion
     * @return TransactionSignature
     */
    public function calculateSignature(PrivateKeyInterface $key, ScriptInterface $scriptCode, $sigVersion)
    {
        if ($sigVersion == 1) {
            $hasher = new V1Hasher($this->tx, $this->txOut->getValue());
        } else {
            $hasher = new Hasher($this->tx);
        }

        $hash = $hasher->calculate($scriptCode, $this->nInput, $this->sigHashType);

        return new TransactionSignature(
            $this->ecAdapter,
            $this->ecAdapter->sign(
                $hash,
                $key,
                new Rfc6979(
                    $this->ecAdapter,
                    $key,
                    $hash,
                    'sha256'
                )
            ),
            $this->sigHashType
        );
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        return $this->requiredSigs !== 0 && $this->requiredSigs === count($this->signatures);
    }

    /**
     * The function only returns true when $scriptPubKey could be classified
     *
     * @param PrivateKeyInterface $key
     * @param ScriptInterface $scriptPubKey
     * @param string $outputType
     * @param BufferInterface[] $results
     * @param int $sigVersion
     * @return bool
     */
    private function doSignature(PrivateKeyInterface $key, ScriptInterface $scriptPubKey, &$outputType, array &$results, $sigVersion = 0)
    {
        $return = [];
        $outputType = $this->classifier->classify($scriptPubKey, $return);
        if ($outputType === OutputClassifier::UNKNOWN) {
            throw new \RuntimeException('Cannot sign unknown script type');
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEY) {
            $publicKeyBuffer = $return;
            $results[] = $publicKeyBuffer;
            $this->requiredSigs = 1;
            $publicKey = PublicKeyFactory::fromHex($publicKeyBuffer);

            if ($publicKey->getBinary() === $key->getPublicKey()->getBinary()) {
                $this->signatures[0] = $this->calculateSignature($key, $scriptPubKey, $sigVersion);
            }

            return true;
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEYHASH) {
            /** @var BufferInterface $pubKeyHash */
            $pubKeyHash = $return;
            $results[] = $pubKeyHash;
            $this->requiredSigs = 1;
            if ($pubKeyHash->getBinary() === $key->getPublicKey()->getPubKeyHash()->getBinary()) {
                $this->signatures[0] = $this->calculateSignature($key, $scriptPubKey, $sigVersion);
                $this->publicKeys[0] = $key->getPublicKey();
            }

            return true;
        }

        if ($outputType === OutputClassifier::MULTISIG) {
            $info = new Multisig($scriptPubKey);

            foreach ($info->getKeys() as $publicKey) {
                $results[] = $publicKey->getBuffer();
            }

            $this->publicKeys = $info->getKeys();
            $this->requiredSigs = $info->getKeyCount();

            foreach ($this->publicKeys as $keyIdx => $publicKey) {
                if ($publicKey->getBinary() == $key->getPublicKey()->getBinary()) {
                    $this->signatures[$keyIdx] = $this->calculateSignature($key, $scriptPubKey, $sigVersion);
                }
            }

            return true;
        }

        if ($outputType === OutputClassifier::PAYTOSCRIPTHASH) {
            /** @var BufferInterface $scriptHash */
            $scriptHash = $return;
            $results[] = $scriptHash;
            return true;
        }

        if ($outputType === OutputClassifier::WITNESS_V0_KEYHASH) {
            /** @var BufferInterface $pubKeyHash */
            $pubKeyHash = $return;
            $results[] = $pubKeyHash;
            $this->requiredSigs = 1;

            if ($pubKeyHash->getBinary() === $key->getPublicKey()->getPubKeyHash()->getBinary()) {
                $script = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $pubKeyHash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
                $this->signatures[0] = $this->calculateSignature($key, $script, 1);
                $this->publicKeys[0] = $key->getPublicKey();
            }

            return true;
        }

        if ($outputType === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            /** @var BufferInterface $scriptHash */
            $scriptHash = $return;
            $results[] = $scriptHash;

            return true;
        }

        return false;
    }

    /**
     * @param PrivateKeyInterface $key
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @return bool
     */
    public function sign(PrivateKeyInterface $key, ScriptInterface $redeemScript = null, ScriptInterface $witnessScript = null)
    {
        /** @var BufferInterface[] $return */
        $type = null;
        $return = [];
        $solved = $this->doSignature($key, $this->txOut->getScript(), $type, $return, 0);

        if ($solved && $type === OutputClassifier::PAYTOSCRIPTHASH) {
            $redeemScriptBuffer = $return[0];

            if (!$redeemScript instanceof ScriptInterface) {
                throw new \InvalidArgumentException('Must provide redeem script for P2SH');
            }

            if (!$redeemScript->getScriptHash()->getBinary() === $redeemScriptBuffer->getBinary()) {
                throw new \InvalidArgumentException("Incorrect redeem script - hash doesn't match");
            }

            $results = []; // ???
            $solved = $solved && $this->doSignature($key, $redeemScript, $type, $results, 0) && $type !== OutputClassifier::PAYTOSCRIPTHASH;
            if ($solved) {
                $this->redeemScript = $redeemScript;
            }
        }

        if ($solved && $type === OutputClassifier::WITNESS_V0_KEYHASH) {
            $pubKeyHash = $return[0];
            $witnessScript = ScriptFactory::sequence([Opcodes::OP_DUP, Opcodes::OP_HASH160, $pubKeyHash, Opcodes::OP_EQUALVERIFY, Opcodes::OP_CHECKSIG]);
            $subType = null;
            $subResults = [];
            $solved = $solved && $this->doSignature($key, $witnessScript, $subType, $subResults, 1);
        } else if ($solved && $type === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $scriptHash = $return[0];

            if (!$witnessScript instanceof ScriptInterface) {
                throw new \InvalidArgumentException('Must provide witness script for witness v0 scripthash');
            }

            if (!Hash::sha256($witnessScript->getBuffer())->getBinary() === $scriptHash->getBinary()) {
                throw new \InvalidArgumentException("Incorrect witness script - hash doesn't match");
            }

            $subType = null;
            $subResults = [];

            $solved = $solved && $this->doSignature($key, $witnessScript, $subType, $subResults, 1)
                && $subType !== OutputClassifier::PAYTOSCRIPTHASH
                && $subType !== OutputClassifier::WITNESS_V0_SCRIPTHASH
                && $subType !== OutputClassifier::WITNESS_V0_KEYHASH;

            if ($solved) {
                $this->witnessScript = $witnessScript;
            }
        }

        return $solved;
    }

    /**
     * @param string $outputType
     * @param $answer
     * @return bool
     */
    private function serializeSimpleSig($outputType, &$answer)
    {
        if ($outputType === OutputClassifier::UNKNOWN) {
            throw new \RuntimeException('Cannot sign unknown script type');
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEY && $this->isFullySigned()) {
            $answer = new SigValues(ScriptFactory::sequence([$this->signatures[0]->getBuffer()]), new ScriptWitness([]));
            return true;
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEYHASH && $this->isFullySigned()) {
            $answer = new SigValues(ScriptFactory::sequence([$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()]), new ScriptWitness([]));
            return true;
        }

        if ($outputType === OutputClassifier::MULTISIG) {
            $sequence = [Opcodes::OP_0];
            $nPubKeys = count($this->publicKeys);
            for ($i = 0; $i < $nPubKeys; $i++) {
                if (isset($this->signatures[$i])) {
                    $sequence[] = $this->signatures[$i]->getBuffer();
                }
            }

            $answer = new SigValues(ScriptFactory::sequence($sequence), new ScriptWitness([]));
            return true;
        }

        return false;
    }

    /**
     * @return SigValues
     */
    public function serializeSignatures()
    {
        static $emptyScript = null;
        static $emptyWitness = null;
        if (is_null($emptyScript) || is_null($emptyWitness)) {
            $emptyScript = new Script();
            $emptyWitness = new ScriptWitness([]);
        }

        /** @var BufferInterface[] $return */
        $outputType = $this->classifier->classify($this->txOut->getScript());

        /** @var SigValues $answer */
        $answer = new SigValues($emptyScript, $emptyWitness);
        $serialized = $this->serializeSimpleSig($outputType, $answer);

        $p2sh = false;
        if (!$serialized && $outputType === OutputClassifier::PAYTOSCRIPTHASH) {
            $p2sh = true;
            $outputType = $this->classifier->classify($this->redeemScript);
            $serialized = $this->serializeSimpleSig($outputType, $answer);
        }

        if (!$serialized && $outputType === OutputClassifier::WITNESS_V0_KEYHASH) {
            $answer = new SigValues($emptyScript, new ScriptWitness([$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()]));
        } else if (!$serialized && $outputType === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $outputType = $this->classifier->classify($this->witnessScript);
            $serialized = $this->serializeSimpleSig($outputType, $answer);

            if ($serialized) {
                $data = [];
                foreach ($answer->getScriptSig()->getScriptParser()->decode() as $o) {
                    $data[] = $o->getData();
                }

                $data[] = $this->witnessScript->getBuffer();
                $answer = new SigValues($emptyScript, new ScriptWitness($data));
            }
        }

        if ($p2sh) {
            $answer = new SigValues(
                ScriptFactory::create($answer->getScriptSig()->getBuffer())->push($this->redeemScript->getBuffer())->getScript(),
                $answer->getScriptWitness()
            );
        }

        return $answer;
    }
}
