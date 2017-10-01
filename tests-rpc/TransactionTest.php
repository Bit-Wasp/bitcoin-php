<?php

namespace BitWasp\Bitcoin\RpcTest;


use BitWasp\Bitcoin\Key\PrivateKeyFactory;
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
     * Check tests are being run against regtest
     */
    public function testIfRegtest()
    {
        $result = $this->makeRpcRequest("getblockchaininfo");
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('chain', $result['result']);
        $this->assertEquals('regtest', $result['result']['chain']);
    }

    /**
     * Produces scripts for signing
     * @return array
     */
    public function getScriptVectors()
    {
        return $this->jsonDataFile('signer_fixtures.json')['valid'];
    }

    /**
     * Produces ALL vectors
     * @return array
     */
    public function getVectors()
    {
        $vectors = [];
        foreach ($this->getScriptVectors() as $fixture) {
            $vectors[] = [$this->stripTestData($fixture)];
        }

        return $vectors;
    }

    /**
     * @param array $fixture
     * @return array
     */
    public function stripTestData(array $fixture)
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
    public function fundOutput(ScriptInterface $script, $value = 100000000)
    {
        $chainInfo = $this->makeRpcRequest('getblockchaininfo');
        $bestHeight = $chainInfo['result']['blocks'];

        while($bestHeight < 150 || $chainInfo['result']['bip9_softforks']['segwit']['status'] !== 'active') {
            $this->makeRpcRequest("generate", [1]);
            $chainInfo = $this->makeRpcRequest('getblockchaininfo');
            $bestHeight = $chainInfo['result']['blocks'];
        }

        $builder = new TxBuilder();
        $builder->output($value, $script);
        $hex = $builder->get()->getHex();

        $result = $this->makeRpcRequest('fundrawtransaction', [$hex, ['feeRate'=>0.0001]]);
        $unsigned = $result['result']['hex'];
        $result = $this->makeRpcRequest('signrawtransaction', [$unsigned]);
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

        $result = $this->makeRpcRequest('sendrawtransaction', [$signedHex]);
        $txid = $result['result'];
        $this->makeRpcRequest("generate", [1]);

        return new Utxo(new OutPoint(Buffer::hex($txid), $outIdx), new TransactionOutput($value, $script));
    }

    /**
     * @param $fixture
     * @dataProvider getVectors
     */
    public function testCases($fixture)
    {
        $defaultPolicy = Interpreter::VERIFY_NONE | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_WITNESS | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY | Interpreter::VERIFY_CHECKSEQUENCEVERIFY;;
        $txBuilder = new TxBuilder();
        if (array_key_exists('version', $fixture['raw'])) {
            $txBuilder->version((int) $fixture['raw']['version']);
        }

        $totalOut = 12345;
        foreach ($fixture['raw']['outs'] as $output) {
            $txBuilder->output($output['value'], ScriptFactory::fromHex($output['script']));
            $totalOut += $output['value'];
        }

        /**
         * @var SignData[] $signDatas
         * @var Utxo[] $utxos
         */
        $signDatas = [];
        $utxos = [];
        foreach ($fixture['raw']['ins'] as $input) {
            $scriptPubKey = ScriptFactory::fromHex($input['scriptPubKey']);
            if (array_key_exists('value', $input)) {
                echo "needs value: {$input['value']}\n";
            }

            $value = array_key_exists('value', $input) ? (int) $input['value'] : $totalOut;
            $utxo = $this->fundOutput($scriptPubKey, $value);

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
        foreach ($fixture['raw']['ins'] as $i => $input) {
            $iSigner = $signer->input($i, $utxos[$i]->getOutput(), $signDatas[$i]);
            foreach ($input['keys'] as $key) {
                $priv = PrivateKeyFactory::fromWif($key['key'], null, NetworkFactory::bitcoinTestnet());
                $sigHashType = $key['sigHashType'];
                $iSigner->sign($priv, $sigHashType);
            }

            $this->assertTrue($iSigner->isFullySigned());
        }

        $tx = $signer->get();
        $result = $this->makeRpcRequest('sendrawtransaction', [$tx->getHex(), true]);
        $this->assertEquals(null, $result['error']);
        
        $txid = $result['result'];
        $this->assertEquals(64, strlen($txid));
    }
}
