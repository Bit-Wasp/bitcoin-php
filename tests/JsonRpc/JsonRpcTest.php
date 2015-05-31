<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31/05/15
 * Time: 17:05
 */

namespace BitWasp\Bitcoin\Tests\JsonRpc;


use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class JsonRpcTest extends AbstractTestCase
{
    public function getResult()
    {
        return [
            [
                1,
                ['error' => ['code'=> 1, 'message' => '']]
            ],
            [
                1,
                ['error' => ['code'=> -32601, 'message' => '']]
            ],
            [
                1,
                ['error' => ['code'=> -32602, 'message' => '']]
            ],
            [
                0,
                ['result' => []]
            ],
        ];
    }

    /**
     * @dataProvider getResult
     * @param $payload
     */
    public function testGetResult($flag, $payload)
    {
        $json = new JsonRpcClient('127.0.0.1', 99999);
        if ($flag == 1) {
            try {
                $json->getResult($payload);
                $this->fail('very fail');
            } catch (\Exception $e) {
                $this->assertTrue(!!$e);
            }
            // should fail
        } else {
            try {
                $json->getResult($payload);
            } catch (\Exception $e) {
                $this->fail('very fail');
            }
            $this->assertTrue(!isset($e));
            // should produce array
        }
    }
}