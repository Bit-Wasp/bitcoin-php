<?php

namespace BitWasp\Bitcoin\Network\Structure;

interface NetworkAddressInterface
{
    public function getServices();
    public function getIp();
    public function getPort();
}
