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
     * @param int $convention
     */
    public function __construct(AddressInterface $address = null, int $convention = self::BIP0021)
    {
        if ($convention === self::BIP0021) {
            if ($address === null) {
                throw new \InvalidArgumentException('Cannot provide a null address with bip0021');
            }
        } else if ($convention !== self::BIP0072) {
            throw new \InvalidArgumentException("Invalid convention for bitcoin uri");
        }

        $this->address = $address;
        $this->rule = $convention;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setAmountBtc(float $value)
    {
        $this->amount = $value;
        return $this;
    }

    /**
     * @param Amount $amount
     * @param int $value
     * @return $this
     */
    public function setAmount(Amount $amount, int $value)
    {
        $this->amount = $amount->toBtc($value);
        return $this;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setRequestUrl(string $url)
    {
        $this->request = $url;
        return $this;
    }

    /**
     * @param NetworkInterface|null $network
     * @return string
     */
    public function uri(NetworkInterface $network = null): string
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
