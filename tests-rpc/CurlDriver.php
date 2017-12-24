<?php

declare(strict_types=1);

/**
 * @author Joshua Estes
 * @copyright 2012-2015 Joshua Estes
 * @license https://github.com/nbobtc/bitcoind-php/blob/2.x/LICENSE MIT
 */

namespace BitWasp\Bitcoin\RpcTest;

use Nbobtc\Http\Driver\DriverInterface;
use Psr\Http\Message\RequestInterface;
use Nbobtc\Http\Message\Response;

/**
 * Uses cURL to send Requests
 *
 * @since 2.0.0
 */
class CurlDriver implements DriverInterface
{
    /**
     * @var resource
     */
    protected $ch;

    /**
     * @var array
     */
    protected $curlOptions = array();

    /**
     * @since 2.0.0
     */
    public function __destruct()
    {
        if (null !== $this->ch) {
            curl_close($this->ch);
            $this->ch=null;
        }
    }

    /**
     * @since 2.0.0
     * {@inheritDoc}
     */
    public function execute(RequestInterface $request)
    {
        $uri = $request->getUri();

        if (null === $this->ch) {
            $this->ch = curl_init();
        }

        curl_setopt_array($this->ch, $this->getDefaultCurlOptions());

        curl_setopt($this->ch, CURLOPT_URL, sprintf('%s://%s@%s', $uri->getScheme(), $uri->getUserInfo(), $uri->getHost()));
        curl_setopt($this->ch, CURLOPT_PORT, $uri->getPort());

        $headers = array();
        foreach ($request->getHeaders() as $header => $values) {
            $headers[] = $header.': '.implode(', ', $values);
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $request->getBody()->getContents());

        // Allows user to override any option, may cause errors
        curl_setopt_array($this->ch, $this->curlOptions);

        /** @var string|false */
        $result = curl_exec($this->ch);
        /** @var array|false */
        $info = curl_getinfo($this->ch);
        /** @var string */
        $error = curl_error($this->ch);

        if (!empty($error)) {
            throw new \Exception($error);
        }

        $response = new Response();
        $response->withStatus($info['http_code']);
        $response->getBody()->write($result);

        return $response;
    }

    /**
     * Add options to use for cURL requests
     *
     * @since 2.0.0
     * @param integer $option
     * @param mixed   $value
     */
    public function addCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;

        return $this;
    }

    /**
     * Returns an array of cURL options
     *
     * @since 2.0.0
     * @return array
     */
    protected function getDefaultCurlOptions()
    {
        return array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
        );
    }
}
