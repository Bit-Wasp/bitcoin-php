<?php

namespace Address;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\Script\ScriptFactory;
use Symfony\Component\Yaml\Yaml;

class AddressTest extends \PHPUnit_Framework_TestCase {

    public function getVectors()
    {
        $datasets = [];
        $yaml = new Yaml();

        $data = $yaml->parse(__DIR__ . '/../Data/addresstests.yml');
        foreach ($data['scriptHash'] as $vector) {
            $datasets[] = [
                'script',
                Bitcoin::getNetwork(),
                $vector['script'],
                $vector['address'],
            ];
        }
        foreach ($data['pubKeyHash'] as $vector) {
            $datasets[] = [
                'pubkeyhash',
                Bitcoin::getNetwork(),
                $vector['publickey'],
                $vector['address'],
            ];
        }

        return $datasets;
    }

    /**
     * @dataProvider getVectors
     */
    public function testAddress($type, $network, $data, $address)
    {
        if ($type == 'pubkeyhash') {
            $obj = PublicKeyFactory::fromHex($data)->getAddress($network);
        } else if ($type == 'script') {
            $obj = ScriptFactory::fromHex($data)->getAddress($network);
        } else {
            throw new \Exception('Unknown address type');
        }

        $this->assertEquals($obj->getAddress(), $address);
    }
}