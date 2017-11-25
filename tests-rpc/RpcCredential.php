<?php

namespace BitWasp\Bitcoin\RpcTest;


class RpcCredential
{
    const CONFIG_TEMPLATE = <<<EOF
rpcuser=%s
rpcpassword=%s
rpcport=%d
rpcallowip=127.0.0.1
server=1
daemon=1
regtest=1
EOF;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $isHttps;

    /**
     * RpcCredential constructor.
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param bool $isHttps
     */
    public function __construct($host, $port, $user, $pass, $isHttps = false)
    {
        $this->host = $host;
        $this->username = $user;
        $this->port = $port;
        $this->password = $pass;
        $this->isHttps = $isHttps;
    }

    /**
     * @return array
     */
    public function getConfigArray()
    {
        return [
            "rpcuser" => $this->username,
            "rpcpassword" => $this->password,
            "rpcport" => $this->port,
            "rpcallowip" => "127.0.0.1",
        ];
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        $prefix = "http" . ($this->isHttps ? "s" : "");
        return "$prefix://{$this->username}:{$this->password}@{$this->host}:{$this->port}";
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isHttps()
    {
        return $this->isHttps;
    }

}
