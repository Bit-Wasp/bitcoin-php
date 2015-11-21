<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;

class Uri
{
    const BIP0021 = 0;
    const BIP0072 = 1;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var null|int
     */
    private $amount;

    /**
     * @var
     */
    private $label;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $request;

    /**
     * @var int
     */
    private $rule;

    /**
     * Uri constructor.
     * @param AddressInterface|null $address
     * @param int $rule
     */
    public function __construct(AddressInterface $address = null, $rule = self::BIP0021)
    {
        if ($rule === self::BIP0021) {
            if ($address === null) {
                throw new \InvalidArgumentException('Cannot provide a null address with bip0021');
            }
        }

        $this->address = $address;
        $this->rule = $rule;
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
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
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
    public function uri(NetworkInterface $network = null)
    {
        if ($this->rule === self::BIP0072) {
            $address = $this->address === null ? '' : $this->address->getAddress($network);
        } else {
            $address = $this->address->getAddress($network);
        }

        $url = 'bitcoin:' . $address;

        $params = [];
        if (null !== $this->amount) {
            $params['amount'] = $this->amount;
        }

        if (null !== $this->label) {
            $params['label'] = $this->label;
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
