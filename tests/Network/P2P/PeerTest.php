<?php

namespace BitWasp\Bitcoin\Tests\Network\P2P;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\P2P\Peer;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use React\EventLoop\StreamSelectLoop;
use React\Socket\Server;
use React\SocketClient\Connector;

class PeerTest
{
    protected function expectCallable($type)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');
        return $mock;
    }

    protected function createCallableMock()
    {
        return $this->getMock('BitWasp\Bitcoin\Tests\Network\P2P\CallableStub');
    }

    private function createResolverMock()
    {
        return $this->getMockBuilder('React\Dns\Resolver\Resolver')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function services($int)
    {
        $math = Bitcoin::getMath();
        $hex = $math->decHex($int);
        $buffer = Buffer::hex($hex, 16);
        return $buffer;
    }

    public function testPeer()
    {

        $localhost = '127.0.0.1';
        $localport = '8333';

        $remotehost = '127.0.0.1';
        $remoteport = '9999';

        $loop = new StreamSelectLoop();
        $network = Bitcoin::getDefaultNetwork();
        $resolver = $this->createResolverMock();

        $server = new Server($loop);
        $server->on('connection', $this->expectCallable($this->once()));
        $server->on('connection', function ($server) {
            $server->close();
        });
        $server->listen($remoteport, $remotehost);

        $local = new NetworkAddress(
            $this->services(1),
            $localhost,
            $localport
        );

        $remote = new NetworkAddress(
            $this->services(1),
            $remotehost,
            $remoteport
        );

        $msgs = new MessageFactory(
            $network,
            new Random()
        );

        $connector = new Connector(
            $loop,
            $resolver
        );

        $peer = new Peer(
            $remote,
            $local,
            $connector,
            $msgs,
            $loop
        );

        $capturedStream = null;
        /** not ready */
    }
}
