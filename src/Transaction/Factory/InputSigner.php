<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
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
     * @var OutputData $scriptPubKey
     */
    private $scriptPubKey;

    /**
     * @var OutputData $redeemScript
     */
    private $redeemScript;

    /**
     * @var OutputData $witnessScript
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
     * @param SignData $signData
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, SignData $signData)
    {
        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->classifier = new OutputClassifier();
        $this->publicKeys = [];
        $this->signatures = [];

        $this->solve($signData);
        $this->extractSignatures();
    }

    /**
     * @param int $sigVersion
     * @param TransactionSignatureInterface[] $stack
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
                for ($i = 1, $j = $size - 1; $i < $j; $i++) {
                    $vars[] = TransactionSignatureFactory::fromHex($stack[$i], $this->ecAdapter);
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
     * @param SignData $signData
     * @return $this
     * @throws \Exception
     */
    private function solve(SignData $signData)
    {
        $scriptPubKey = $this->txOut->getScript();
        $solution = $this->scriptPubKey = $this->classifier->decode($scriptPubKey);
        if ($solution->getType() === OutputClassifier::UNKNOWN) {
            throw new \RuntimeException('scriptPubKey type is unknown');
        }

        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $redeemScript = $signData->getRedeemScript();
            if (!$solution->getSolution()->equals(Hash::sha256ripe160($redeemScript->getBuffer()))) {
                throw new \Exception('Redeem script doesn\'t match script-hash');
            }
            $solution = $this->redeemScript = $this->classifier->decode($redeemScript);
            if (!in_array($solution->getType(), [OutputClassifier::WITNESS_V0_SCRIPTHASH, OutputClassifier::WITNESS_V0_KEYHASH, OutputClassifier::PAYTOPUBKEYHASH , OutputClassifier::PAYTOPUBKEY, OutputClassifier::MULTISIG])) {
                throw new \Exception('Unsupported pay-to-script-hash script');
            }
        }
        // WitnessKeyHash doesn't require further solving until signing
        if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $witnessScript = $signData->getWitnessScript();
            if (!$solution->getSolution()->equals(Hash::sha256($witnessScript->getBuffer()))) {
                throw new \Exception('Witness script doesn\'t match witness-script-hash');
            }
            $solution = $this->witnessScript = $this->classifier->decode($witnessScript);
            if (!in_array($solution->getType(), [OutputClassifier::PAYTOPUBKEYHASH , OutputClassifier::PAYTOPUBKEY, OutputClassifier::MULTISIG])) {
                throw new \Exception('Unsupported witness-script-hash script');
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function extractSignatures()
    {
        $solution = $this->scriptPubKey;
        $scriptSig = $this->tx->getInput($this->nInput)->getScript();
        if (in_array($solution->getType(), [OutputClassifier::PAYTOPUBKEYHASH , OutputClassifier::PAYTOPUBKEY, OutputClassifier::MULTISIG])) {
            $stack = [];
            foreach ($scriptSig->getScriptParser()->decode() as $op) {
                $stack[] = $op->getData();
            }
            $this->extractFromValues($solution->getType(), $solution->getScript(), $stack, 0);
        }

        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $decodeSig = $scriptSig->getScriptParser()->decode();
            if (count($decodeSig) > 0) {
                $redeemScript = new Script(end($decodeSig)->getData());
                if (!$redeemScript->getBuffer()->equals($this->redeemScript->getScript()->getBuffer())) {
                    throw new \RuntimeException('Redeem script from scriptSig doesn\'t match script-hash');
                }

                $internalSig = [];
                foreach (array_slice($decodeSig, 0, -1) as $operation) {
                    /** @var Operation $operation */
                    $internalSig[] = $operation->getData();
                }

                $solution = $this->redeemScript;
                $this->extractFromValues($solution->getType(), $solution->getScript(), $internalSig, 0);
            }
        }

        $witnesses = $this->tx->getWitnesses();
        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $wit = isset($witnesses[$this->nInput]) ? $witnesses[$this->nInput]->all() : [];
            $keyHashCode = ScriptFactory::scriptPubKey()->payToPubKeyHashFromHash($solution->getSolution());
            $this->extractFromValues(OutputClassifier::PAYTOPUBKEYHASH, $keyHashCode, $wit, 1);
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if (isset($witnesses[$this->nInput])) {
                $witness = $witnesses[$this->nInput];
                $witCount = count($witnesses[$this->nInput]);
                if ($witCount > 0) {
                    if (!$witness[$witCount - 1]->equals($this->witnessScript->getScript()->getBuffer())) {
                        throw new \RuntimeException('Redeem script from scriptSig doesn\'t match script-hash');
                    }

                    $solution = $this->witnessScript;
                    $this->extractFromValues($solution->getType(), $solution->getScript(), array_slice($witness->all(), 0, -1), 1);
                }
            }
        }

        return $this;
    }

    /**
     * @param ScriptInterface $scriptCode
     * @param int $sigHashType
     * @param int $sigVersion
     * @return BufferInterface
     */
    public function calculateSigHash(ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        if ($sigVersion === 1) {
            $hasher = new V1Hasher($this->tx, $this->txOut->getValue());
        } else {
            $hasher = new Hasher($this->tx);
        }

        return $hasher->calculate($scriptCode, $this->nInput, $sigHashType);
    }

    /**
     * @param PrivateKeyInterface $key
     * @param ScriptInterface $scriptCode
     * @param int $sigHashType
     * @param int $sigVersion
     * @return TransactionSignature
     */
    public function calculateSignature(PrivateKeyInterface $key, ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        $hash = $this->calculateSigHash($scriptCode, $sigHashType, $sigVersion);
        $ecSignature = $this->ecAdapter->sign($hash, $key, new Rfc6979($this->ecAdapter, $key, $hash, 'sha256'));
        return new TransactionSignature($this->ecAdapter, $ecSignature, $sigHashType);
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
     * @param OutputData $solution
     * @param int $sigHashType
     * @param int $sigVersion
     */
    private function doSignature(PrivateKeyInterface $key, OutputData $solution, $sigHashType, $sigVersion = 0)
    {
        if ($solution->getType() === OutputClassifier::PAYTOPUBKEY) {
            if (!$key->getPublicKey()->getBuffer()->equals($solution->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $solution->getScript(), $sigHashType, $sigVersion);
            $this->publicKeys[0] = $key->getPublicKey();
            $this->requiredSigs = 1;
        } else if ($solution->getType() === OutputClassifier::PAYTOPUBKEYHASH) {
            if (!$key->getPubKeyHash()->equals($solution->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $solution->getScript(), $sigHashType, $sigVersion);
            $this->publicKeys[0] = $key->getPublicKey();
            $this->requiredSigs = 1;
        } else if ($solution->getType() === OutputClassifier::MULTISIG) {
            $info = new Multisig($solution->getScript());
            $this->publicKeys = $info->getKeys();
            $this->requiredSigs = $info->getKeyCount();

            $myKey = $key->getPublicKey()->getBuffer();
            $signed = false;
            foreach ($info->getKeys() as $keyIdx => $publicKey) {
                if ($publicKey->getBuffer()->equals($myKey)) {
                    $this->signatures[$keyIdx] = $this->calculateSignature($key, $solution->getScript(), $sigHashType, $sigVersion);
                    $signed = true;
                }
            }

            if (!$signed) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
        } else {
            throw new \RuntimeException('Cannot sign unknown script type');
        }
    }

    /**
     * @param PrivateKeyInterface $key
     * @param int $sigHashType
     * @return bool
     */
    public function sign(PrivateKeyInterface $key, $sigHashType = SigHashInterface::ALL)
    {
        if ($this->scriptPubKey->canSign()) {
            $this->doSignature($key, $this->scriptPubKey, $sigHashType, 0);
            return true;
        }
        $solution = $this->scriptPubKey;
        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            if ($this->redeemScript->canSign()) {
                $this->doSignature($key, $this->redeemScript, $sigHashType, 0);
                return true;
            }
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $keyHashScript = ScriptFactory::scriptPubKey()->payToPubKeyHashFromHash($solution->getSolution());
            $this->doSignature($key, $this->classifier->decode($keyHashScript), $sigHashType, 1);
            return true;
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if ($this->witnessScript->canSign()) {
                $this->doSignature($key, $this->witnessScript, $sigHashType, 1);
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $outputType
     * @return SigValues
     */
    private function serializeSimpleSig($outputType)
    {
        if (!in_array($outputType, [OutputClassifier::PAYTOPUBKEY, OutputClassifier::PAYTOPUBKEYHASH, OutputClassifier::MULTISIG])) {
            throw new \RuntimeException('Cannot serialize this script sig');
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEY && $this->isFullySigned()) {
            return new SigValues(ScriptFactory::sequence([$this->signatures[0]->getBuffer()]), new ScriptWitness([]));
        }

        if ($outputType === OutputClassifier::PAYTOPUBKEYHASH && $this->isFullySigned()) {
            return new SigValues(ScriptFactory::sequence([$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()]), new ScriptWitness([]));
        }

        if ($outputType === OutputClassifier::MULTISIG) {
            $sequence = [Opcodes::OP_0];
            for ($i = 0, $nPubKeys = count($this->publicKeys); $i < $nPubKeys; $i++) {
                if (isset($this->signatures[$i])) {
                    $sequence[] = $this->signatures[$i]->getBuffer();
                }
            }

            return new SigValues(ScriptFactory::sequence($sequence), new ScriptWitness([]));
        }
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

        /** @var SigValues $answer */
        $answer = new SigValues($emptyScript, $emptyWitness);
        $solution = $this->scriptPubKey;
        if ($solution->canSign()) {
            $answer = $this->serializeSimpleSig($this->scriptPubKey->getType());
        }

        $p2sh = false;
        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $p2sh = true;
            if ($this->redeemScript->canSign()) {
                $answer = $this->serializeSimpleSig($this->redeemScript->getType());
            }
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $answer = new SigValues($emptyScript, new ScriptWitness([$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()]));
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if ($this->witnessScript->canSign()) {
                $answer = $this->serializeSimpleSig($this->witnessScript->getType());
                $data = [];
                foreach ($answer->getScriptSig()->getScriptParser()->decode() as $o) {
                    $data[] = $o->getData();
                }

                $data[] = $this->witnessScript->getScript()->getBuffer();
                $answer = new SigValues($emptyScript, new ScriptWitness($data));
            }
        }

        if ($p2sh) {
            $answer = new SigValues(
                ScriptFactory::create($answer->getScriptSig()->getBuffer())->push($this->redeemScript->getScript()->getBuffer())->getScript(),
                $answer->getScriptWitness()
            );
        }

        return $answer;
    }
}
