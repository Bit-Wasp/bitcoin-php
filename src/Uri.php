<?php

namespace BitWasp\Bitcoin;


use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;

class Uri
{
    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var null|int
     */
    private $amount;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $request;

    /**
     * Uri constructor.
     * @param AddressInterface $address
     */
    public function __construct(AddressInterface $address)
    {
        $this->address = $address;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setAmountBtc($value)
    {
        $this->amount = $value;
        return $this;
    }

    /**
     * @param Amount $amount
     * @param int $value
     * @return $this
     */
    public function setAmount(Amount $amount, $value)
    {
        $this->amount = $amount->toBtc($value);
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setRequestUrl($url)
    {
        $this->request = $url;
        return $this;
    }

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function url(NetworkInterface $network = null)
    {
        $url = 'bitcoin:' . $this->address->getAddress($network);

        $params = [];
        if (null !== $this->amount) {
            $params['amount'] = $this->amount;
        }

        if (null !== $this->message) {
            $params['message'] = $this->message;
        }

        if (null !== $this->request) {
            $params['r'] = $this->request;
        }

        if (count($params) > 0) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}