<?php

namespace BitWasp\Bitcoin\Rpc\Client;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    protected $client;

    /**
     * @param JsonRpcClient $client
     */
    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
    }

    public function getinfo()
    {
        $info = $this->client->execute('getinfo');
        return $info;
    }

    /**
     * @return mixed
     */
    public function getbestblockhash()
    {
        $hash = $this->client->execute('getbestblockhash');
        return $hash;
    }

    /**
     * @param $blockHeight
     * @return mixed
     */
    public function getblockhash($blockHeight)
    {
        $hash = $this->client->execute('getblockhash', array($blockHeight));
        return $hash;
    }

    /**
     * @param $blockhash
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function getblock($blockhash)
    {
        $blockArray = $this->client->execute('getblock', array($blockhash, true));
        $block = BlockFactory::create();

        // Build block header
        $block->getHeader()
            ->setVersion($blockArray['version'])
            ->setBits(Buffer::hex($blockArray['bits']))
            ->setTimestamp($blockArray['time'])
            ->setMerkleRoot($blockArray['merkleroot'])
            ->setNonce($blockArray['nonce'])
            ->setPrevBlock(@$blockArray['previousblockhash']) // only @ this because of genesis block
            ->setNextBlock(@$blockArray['nextblockhash']);

        // Establish batch query for loading transactions
        if (count($blockArray['tx']) > 0) {
            $this->client->batch();
            foreach ($blockArray['tx'] as $txid) {
                $this->client->execute('getrawtransaction', array($txid));
            }
            $result = $this->client->send();

            // Build the transactions
            $block->getTransactions()->addTransactions(
                array_map(function ($value) {
                    return TransactionFactory::fromHex($value);
                }, $result)
            );

        }

        return $block;
    }

    /**
     * @param $txid
     * @param bool $verbose
     * @return TransactionInterface
     * @throws \Exception
     */
    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', array($txid));
        if ($tx === false) {
            throw new \Exception('FALSE from rpc?');
        }
        if ($verbose) {
            $tx = TransactionFactory::fromHex($tx);
        }

        return $tx;
    }

    /**
     * @param TransactionInterface $transaction
     * @param bool $allowExtremeFees
     * @return mixed
     */
    public function sendrawtransaction(TransactionInterface $transaction, $allowExtremeFees = false)
    {
        $hex = $transaction->getBuffer()->getHex();
        $send = $this->client->execute('sendrawtransaction', array($hex, $allowExtremeFees));
        return $send;
    }
}
