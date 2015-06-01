<?php

namespace BitWasp\Bitcoin\Rpc\Client;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\SignatureHashInterface;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    private $client;

    /**
     * @param JsonRpcClient $client
     */
    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return JsonRpcClient
     */
    public function getRpcClient()
    {
        return $this->client;
    }

    /**
     * @param string $result
     * @throws \Exception
     */
    private function checkNotNull($result)
    {
        if (null === $result) {
            throw new \Exception('Received no response from server');
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getinfo()
    {
        $info = $this->client->execute('getinfo');
        $this->checkNotNull($info);
        return $info;
    }

    /**
     * @return int
     */
    public function getblockcount()
    {
        $count = $this->client->execute('getbestblockhash');
        return $count;
    }

    /**
     * @return string
     */
    public function getbestblockhash()
    {
        $hash = $this->client->execute('getbestblockhash');
        return $hash;
    }

    /**
     * @param int|string $blockHeight
     * @return string
     */
    public function getblockhash($blockHeight)
    {
        $hash = $this->client->execute('getblockhash', [$blockHeight]);
        return $hash;
    }

    /**
     * @param string $blockhash
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function getblock($blockhash)
    {
        $blockArray = $this->client->execute('getblock', [$blockhash, true]);
        $this->checkNotNull($blockArray);

        // Establish batch query for loading transactions
        $txs = [];
        if (count($blockArray['tx']) > 0) {
            $this->client->batch();
            foreach ($blockArray['tx'] as $txid) {
                $this->client->execute('getrawtransaction', array($txid));
            }
            $result = $this->client->send();

            // Build the transactions
            $txs = array_map(
                function ($value) {
                    return TransactionFactory::fromHex($value);
                },
                $result
            );
        }

        // Build block header
        $header = new BlockHeader(
            $blockArray['version'],
            @$blockArray['previousblockhash'], // @ for genesis block.
            $blockArray['merkleroot'],
            $blockArray['time'],
            Buffer::hex($blockArray['bits']),
            $blockArray['nonce']
        );

        if (isset($blockArray['nextblockhash'])) {
            $header->setNextBlock($blockArray['nextblockhash']);
        }

        return new Block(
            Bitcoin::getMath(),
            $header,
            new TransactionCollection($txs)
        );
    }

    /**
     * @param string $txid
     * @param bool $verbose
     * @return TransactionInterface
     * @throws \Exception
     */
    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', [$txid]);
        $this->checkNotNull($tx);

        if ($verbose) {
            $tx = TransactionFactory::fromHex($tx);
        }

        return $tx;
    }

    /**
     * @param $inputs
     * @param $outputs
     * @return mixed
     * @throws \Exception
     */
    public function createrawtransaction($inputs, $outputs)
    {
        $tx = $this->client->execute('createrawtransaction', [$inputs, $outputs]);
        $this->checkNotNull($tx);
        return $tx;
    }

    /**
     * @param TransactionInterface $tx
     * @param array $inputs
     * @param array $privateKeys
     * @param int $sighash
     * @param NetworkInterface $network
     * @return \BitWasp\Bitcoin\Transaction\Transaction
     * @throws \Exception
     */
    public function signrawtransaction(
        TransactionInterface $tx,
        array $inputs = [],
        array $privateKeys = [],
        $sighash = SignatureHashInterface::SIGHASH_ALL,
        NetworkInterface $network = null
    ) {
        $tx = $this->client->execute('signrawtransaction', [
            $tx->getHex(),
            $inputs,
            array_map(
                function (PrivateKeyInterface $privateKey) use ($network) {
                    return $privateKey->toWif($network);
                },
                $privateKeys
            ),
            $sighash
        ]);

        $this->checkNotNull($tx);
        return TransactionFactory::fromHex($tx['hex']);
    }

    /**
     * @param TransactionInterface $transaction
     * @param bool $allowExtremeFees
     * @return string
     */
    public function sendrawtransaction(TransactionInterface $transaction, $allowExtremeFees = false)
    {
        $send = $this->client->execute('sendrawtransaction', [$transaction->getHex(), $allowExtremeFees]);
        $this->checkNotNull($send);
        return $send;
    }

    /**
     * @param int $minConfirms
     * @param int $maxConfirms
     * @param array $addresses
     * @param NetworkInterface $network
     * @return array
     */
    public function listunspent($minConfirms = 1, $maxConfirms = 9999999, array $addresses = [], NetworkInterface $network = null)
    {
        $amount = new Amount();

        $results = $this->client->execute(
            'listunspent',
            [
                $minConfirms,
                $maxConfirms,
                array_map(
                    function (AddressInterface $address) use ($network) {
                        return $address->getAddress($network);
                    },
                    $addresses
                )
            ]
        );

        return array_map(
            function (array $value) use ($amount) {
                return new Utxo(
                    $value['txid'],
                    $value['vout'],
                    new TransactionOutput(
                        $amount->toSatoshis($value['amount']),
                        new Script(Buffer::hex($value['scriptPubKey']))
                    )
                );
            },
            $results
        );
    }
}
