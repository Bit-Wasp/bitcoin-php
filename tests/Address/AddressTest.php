<?php

namespace BitWasp\Bitcoin\Tests\Address;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use Symfony\Component\Yaml\Yaml;

class AddressTest extends AbstractTestCase
{

    /**
     * @return array
     */
    public function getVectors()
    {
        $datasets = [];
        $yaml = new Yaml();

        $data = $yaml->parse(file_get_contents(__DIR__ . '/../Data/addresstests.yml'));
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

        $fromString = AddressFactory::fromString($address);
        $this->assertEquals($address, $obj->getAddress($network));
        $this->assertEquals($obj, $fromString);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddressFailswithBytes()
    {
        $add = 'LPjNgqp43ATwzMTJPM2SFoEYeyJV6pq6By';
        $network = Bitcoin::getNetwork();
        AddressFactory::fromString($add, $network);
    }
}
