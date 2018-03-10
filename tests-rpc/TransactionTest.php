<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class TransactionTest extends AbstractTestCase
{
    /**
     * @var RegtestBitcoinFactory
     */
    private $rpcFactory;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static $rpcFactory = null;
        if (null === $rpcFactory) {
            $rpcFactory = new RegtestBitcoinFactory();
        }
        $this->rpcFactory = $rpcFactory;
    }

    /**
     * Produces scripts for signing
     * @return array
     */
    public function getScriptVectors(): array
    {
        return $this->jsonDataFile('signer_fixtures.json')['valid'];
    }

    /**
     * Produces ALL vectors
     * @return array
     */
    public function getVectors(): array
    {
        $vectors = [];
        foreach ($this->getScriptVectors() as $fixture) {
            if ($fixture['id'] === "1a47e53c26efe81aaf7fceedf447d965b0d5cb26b35d3a9a2b32aa89ae9a979f") {
                continue;
            }
            $vectors[] = [$this->stripTestData($fixture)];
        }

        return $vectors;
    }

    /**
     * @param array $fixture
     * @return array
     */
    public function stripTestData(array $fixture): array
    {
        foreach (['hex'] as $key) {
            if (array_key_exists($key, $fixture)) {
                unset($fixture[$key]);
            }
        }
        foreach ($fixture['raw']['ins'] as &$input) {
            unset($input['hash']);
            unset($input['index']);
            unset($input['value']);
        }
        return $fixture;
    }

    /**
     * @param ScriptInterface $script
     * @param int $value
     * @return Utxo
     */
    public function fundOutput(RpcServer $server, ScriptInterface $script, $value = 100000000): Utxo
    {
        $chainInfo = $server->makeRpcRequest('getblockchaininfo');
        $bestHeight = $chainInfo['result']['blocks'];

        while ($bestHeight < 150 || $chainInfo['result']['bip9_softforks']['segwit']['status'] !== 'active') {
            // ought to finish in 1!
            $server->makeRpcRequest("generate", [435]);
            $chainInfo = $server->makeRpcRequest('getblockchaininfo');
            $bestHeight = $chainInfo['result']['blocks'];
        }

        $builder = new TxBuilder();
        $builder->output($value, $script);
        $hex = $builder->get()->getHex();

        $result = $server->makeRpcRequest('fundrawtransaction', [$hex, ['feeRate'=>0.0001]]);
        $unsigned = $result['result']['hex'];
        $result = $server->makeRpcRequest('signrawtransaction', [$unsigned]);
        $signedHex = $result['result']['hex'];
        $signed = TransactionFactory::fromHex($signedHex);
        $outIdx = -1;
        foreach ($signed->getOutputs() as $i => $output) {
            if ($output->getScript()->equals($script)) {
                $outIdx = $i;
            }
        }

        if ($outIdx === -1) {
            throw new \RuntimeException("Sanity check failed, should have found the output we funded");
        }

        $result = $server->makeRpcRequest('sendrawtransaction', [$signedHex]);
        $txid = $result['result'];
        $server->makeRpcRequest("generate", [1]);

        return new Utxo(new OutPoint(Buffer::hex($txid), $outIdx), new TransactionOutput($value, $script));
    }

    /**
     * @param array $fixture
     * @dataProvider getVectors
     */
    public function testCases(array $fixture)
    {
        $bitcoind = $this->rpcFactory->startBitcoind();
        $this->assertTrue($bitcoind->isRunning());

        $defaultPolicy = Interpreter::VERIFY_NONE | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_WITNESS | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY | Interpreter::VERIFY_CHECKSEQUENCEVERIFY;
        ;
        $txBuilder = new TxBuilder();
        if (array_key_exists('version', $fixture['raw'])) {
            $txBuilder->version((int) $fixture['raw']['version']);
        }

        $totalOut = 12345;
        foreach ($fixture['raw']['outs'] as $output) {
            $value = (int) $output['value'];
            $txBuilder->output($value, ScriptFactory::fromHex($output['script']));
            $totalOut += $value;
        }

        /**
         * @var SignData[] $signDatas
         * @var Utxo[] $utxos
         */
        $signDatas = [];
        $utxos = [];
        foreach ($fixture['raw']['ins'] as $input) {
            $scriptPubKey = ScriptFactory::fromHex($input['scriptPubKey']);

            $value = array_key_exists('value', $input) ? (int) $input['value'] : $totalOut;
            $utxo = $this->fundOutput($bitcoind, $scriptPubKey, $value);

            $sequence = array_key_exists('sequence', $input) ? (int) $input['sequence'] : 0xffffffff;
            $txBuilder->spendOutPoint($utxo->getOutPoint(), null, $sequence);

            $signData = new SignData();
            if (array_key_exists('redeemScript', $input) && "" !== $input['redeemScript']) {
                $signData->p2sh(ScriptFactory::fromHex($input['redeemScript']));
            }
            if (array_key_exists('witnessScript', $input) && "" !== $input['witnessScript']) {
                $signData->p2wsh(ScriptFactory::fromHex($input['witnessScript']));
            }

            $policy = array_key_exists('signaturePolicy', $fixture) ? $this->getScriptFlagsFromString($fixture['signaturePolicy']) : $defaultPolicy;
            $signData->signaturePolicy($policy);
            $signDatas[] = $signData;

            $utxos[] = $utxo;
        }

        $txBuilder->locktime(isset($fixture['raw']['locktime']) ? $fixture['raw']['locktime'] : 0);

        $signer = new Signer($txBuilder->get());
        $privFactory = PrivateKeyFactory::compressed();
        foreach ($fixture['raw']['ins'] as $i => $input) {
            $iSigner = $signer->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);
            foreach ($input['keys'] as $key) {
                $priv = $privFactory->fromWif($key['key'], NetworkFactory::bitcoinTestnet());
                $sigHashType = $key['sigHashType'];
                $iSigner->sign($priv, $sigHashType);
            }

            $this->assertTrue($iSigner->isFullySigned());
        }

        $tx = $signer->get();
        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex(), true]);
        $this->assertEquals(null, $result['error']);
        
        $txid = $result['result'];
        $this->assertEquals(64, strlen($txid));

        $bitcoind->destroy();
    }
}
