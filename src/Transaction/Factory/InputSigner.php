<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Signature\SignatureSort;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class InputSigner
{
    /**
     * @var array
     */
    protected static $canSign = [
        OutputClassifier::PAYTOPUBKEYHASH,
        OutputClassifier::PAYTOPUBKEY,
        OutputClassifier::MULTISIG
    ];

    /**
     * @var array
     */
    protected static $validP2sh = [
        OutputClassifier::WITNESS_V0_KEYHASH,
        OutputClassifier::WITNESS_V0_SCRIPTHASH,
        OutputClassifier::PAYTOPUBKEYHASH,
        OutputClassifier::PAYTOPUBKEY,
        OutputClassifier::MULTISIG
    ];

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
     * @var OutputData $witnessKeyHash
     */
    private $witnessKeyHash;

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
     * @var Interpreter
     */
    private $interpreter;

    /**
     * @var Checker
     */
    private $signatureChecker;

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
        if (!isset($tx->getInputs()[$nInput])) {
            throw new \RuntimeException('No input at this index');
        }

        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->classifier = new OutputClassifier();
        $this->interpreter = new Interpreter();
        $this->signatureChecker = new Checker($this->ecAdapter, $this->tx, $nInput, $txOut->getValue());
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
        $sigSort = new SignatureSort($this->ecAdapter);
        $sigs = new \SplObjectStorage;

        foreach ($stack as $txSig) {
            $hash = $this->calculateSigHash($scriptCode, $txSig->getHashType(), $sigVersion);
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
                if (!$this->evaluateSolution(0, $scriptCode, $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
                $this->signatures = [TransactionSignatureFactory::fromHex($stack[0], $this->ecAdapter)];
                $this->publicKeys = [PublicKeyFactory::fromHex($stack[1], $this->ecAdapter)];
            }
        }

        if ($type === OutputClassifier::PAYTOPUBKEY && count($stack) === 1) {
            $this->requiredSigs = 1;
            if ($size === 1) {
                if (!$this->evaluateSolution(0, $scriptCode, $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
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

                if (count(array_filter($this->signatures, 'is_null')) === count($this->publicKeys)) {
                    if (!$this->evaluateSolution(0, $scriptCode, $stack, $sigVersion)) {
                        throw new \RuntimeException('Existing signatures are invalid!');
                    }
                }
            }
        }

        return $type;
    }

    /**
     * This function must throw an exception whenever execution
     * has not yielded a signable script.
     *
     * @param SignData $signData
     * @return $this
     * @throws \Exception
     */
    private function solve(SignData $signData)
    {
        $flags = Interpreter::VERIFY_NONE;
        $scriptPubKey = $this->txOut->getScript();
        $solution = $this->scriptPubKey = $this->classifier->decode($scriptPubKey);
        if ($solution->getType() === OutputClassifier::UNKNOWN) {
            throw new \RuntimeException('scriptPubKey type is unknown');
        }

        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $redeemScript = $signData->getRedeemScript();
            if (!$this->verifySolution($flags, ScriptFactory::sequence([$redeemScript->getBuffer()]), $solution->getScript())) {
                throw new \Exception('Redeem script fails to solve pay-to-script-hash');
            }
            $solution = $this->redeemScript = $this->classifier->decode($redeemScript);
            if (!in_array($solution->getType(), self::$validP2sh)) {
                throw new \Exception('Unsupported pay-to-script-hash script');
            }
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $this->witnessKeyHash = $this->classifier->decode(ScriptFactory::scriptPubKey()->payToPubKeyHashFromHash($solution->getSolution()));
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $witnessScript = $signData->getWitnessScript();
            if (!$this->verifySolution($flags, ScriptFactory::sequence([$witnessScript->getBuffer()]), $solution->getScript())) {
                throw new \Exception('Redeem script fails to solve pay-to-script-hash');
            }
            $this->witnessScript = $this->classifier->decode($witnessScript);
            if (!in_array($this->witnessScript->getType(), self::$canSign)) {
                throw new \Exception('Unsupported witness-script-hash script');
            }
        }

        return $this;
    }

    /**
     * @param int $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param ScriptWitnessInterface|null $scriptWitness
     * @return bool
     */
    private function verifySolution($flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, ScriptWitnessInterface $scriptWitness = null)
    {
        return $this->interpreter->verify($scriptSig, $scriptPubKey, $flags, $this->signatureChecker, $scriptWitness);
    }

    /**
     * @param int $flags
     * @param ScriptInterface $scriptPubKey
     * @param array $chunks
     * @param int $sigVersion
     * @return bool
     */
    private function evaluateSolution($flags, ScriptInterface $scriptPubKey, array $chunks, $sigVersion)
    {
        return $this->interpreter->evaluate($scriptPubKey, new Stack($chunks), $sigVersion, $flags, $this->signatureChecker);
    }

    /**
     * @return $this
     */
    public function extractSignatures()
    {
        $scriptSig = $this->tx->getInput($this->nInput)->getScript();
        $witnesses = $this->tx->getWitnesses();
        $witness = isset($witnesses[$this->nInput]) ? $witnesses[$this->nInput]->all() : [];

        $solution = $this->scriptPubKey;
        $chunks = [];
        $sigVersion = SigHash::V0;
        if ($solution->canSign()) {
            $chunks = $this->evalPushOnly($scriptSig);
        }

        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $sigChunks = $this->evalPushOnly($scriptSig);
            if (count($sigChunks) > 0) {
                if (!end($sigChunks)->equals($this->redeemScript->getScript()->getBuffer())) {
                    throw new \RuntimeException('Extracted redeemScript did not match script-hash');
                }

                $solution = $this->redeemScript;
                $chunks = array_slice($sigChunks, 0, -1);
            }
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $chunks = $witness;
            $solution = $this->witnessKeyHash;
            $sigVersion = SigHash::V1;
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if (is_array($witness) && count($witness) > 0) {
                if (!end($witness)->equals($this->witnessScript->getScript()->getBuffer())) {
                    throw new \RuntimeException('Extracted redeemScript did not match script-hash');
                }

                $solution = $this->witnessScript;
                $sigVersion = SigHash::V1;
                $chunks = array_slice($witness, 0, -1);
            }
        }

        $this->extractFromValues($solution->getType(), $solution->getScript(), $chunks, $sigVersion);

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
        if ($sigVersion === SigHash::V1) {
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
    private function doSignature(PrivateKeyInterface $key, OutputData $solution, $sigHashType, $sigVersion = SigHash::V0)
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
            $this->requiredSigs = $info->getRequiredSigCount();

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
    public function sign(PrivateKeyInterface $key, $sigHashType = SigHash::ALL)
    {
        $solution = $this->scriptPubKey;
        $sigVersion = SigHash::V0;
        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $solution = $this->witnessKeyHash;
            $sigVersion = SigHash::V1;
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $solution = $this->witnessScript;
            $sigVersion = SigHash::V1;
        }

        if ($solution->canSign()) {
            $this->doSignature($key, $solution, $sigHashType, $sigVersion);
            return true;
        }

        return false;
    }

    /**
     * @param string $outputType
     * @return BufferInterface[]
     */
    private function serializeSolution($outputType)
    {
        if ($outputType === OutputClassifier::PAYTOPUBKEY) {
            return [$this->signatures[0]->getBuffer()];
        } else if ($outputType === OutputClassifier::PAYTOPUBKEYHASH) {
            return [$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()];
        } else if ($outputType === OutputClassifier::MULTISIG) {
            $sequence = [new Buffer()];
            for ($i = 0, $nPubKeys = count($this->publicKeys); $i < $nPubKeys; $i++) {
                if (isset($this->signatures[$i])) {
                    $sequence[] = $this->signatures[$i]->getBuffer();
                }
            }

            return $sequence;
        } else {
            throw new \RuntimeException('Cannot serialize this script sig');
        }
    }

    /**
     * @param ScriptInterface $script
     * @param int $flags
     * @return \BitWasp\Buffertools\BufferInterface[]
     */
    private function evalPushOnly(ScriptInterface $script, $flags = Interpreter::VERIFY_NONE)
    {
        $stack = new Stack();
        $interpreter = new Interpreter();
        $interpreter->evaluate($script, $stack, SigHash::V0, $flags | Interpreter::VERIFY_SIGPUSHONLY, new Checker($this->ecAdapter, $this->tx, $this->nInput, $this->txOut->getValue()));
        return $stack->all();
    }

    /**
     * @param BufferInterface[] $buffers
     * @return ScriptInterface
     */
    public function pushAll(array $buffers)
    {
        return ScriptFactory::sequence(array_map(function ($buffer) {
            if (!($buffer instanceof BufferInterface)) {
                throw new \RuntimeException('Script contained a non-push opcode');
            }

            $size = $buffer->getSize();
            if ($size === 0) {
                return Opcodes::OP_0;
            }

            $first = ord($buffer->getBinary());
            if ($size === 1 && $first >= 1 && $first <= 16) {
                return \BitWasp\Bitcoin\Script\encodeOpN($first);
            } else {
                return $buffer;
            }
        }, $buffers));
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

        $scriptSigChunks = [];
        $witness = [];
        if ($this->scriptPubKey->canSign()) {
            $scriptSigChunks = $this->serializeSolution($this->scriptPubKey->getType());
        }

        $solution = $this->scriptPubKey;
        $p2sh = false;
        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $p2sh = true;
            if ($this->redeemScript->canSign()) {
                $scriptSigChunks = $this->serializeSolution($this->redeemScript->getType());
            }
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $witness = $this->serializeSolution(OutputClassifier::PAYTOPUBKEYHASH);
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if ($this->witnessScript->canSign()) {
                $witness = $this->serializeSolution($this->witnessScript->getType());
                $witness[] = $this->witnessScript->getScript()->getBuffer();
            }
        }

        if ($p2sh) {
            $scriptSigChunks[] = $this->redeemScript->getScript()->getBuffer();
        }

        return new SigValues($this->pushAll($scriptSigChunks), new ScriptWitness($witness));
    }
}
