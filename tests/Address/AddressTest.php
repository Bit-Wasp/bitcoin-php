<?php

namespace Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Symfony\Component\Yaml\Yaml;

class AddressTest extends AbstractTestCase
{

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
     * @param $type
     * @param NetworkInterface $network
     * @param $data
     * @param $address
     * @throws \Exception
     */
    public function testAddress($type, NetworkInterface $network, $data, $address)
    {
        if ($type == 'pubkeyhash') {
            $obj = PublicKeyFactory::fromHex($data)->getAddress();
        } else if ($type == 'script') {
            $obj = ScriptFactory::fromHex($data)->getAddress();
        } else {
            throw new \Exception('Unknown address type');
        }

        $this->assertEquals($obj->getAddress($network), $address);
    }
}