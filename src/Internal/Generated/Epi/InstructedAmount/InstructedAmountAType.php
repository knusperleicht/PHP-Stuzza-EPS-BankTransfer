<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\InstructedAmount;

/**
 * Class representing InstructedAmountAType
 */
class InstructedAmountAType
{
    /**
     * @var string $__value
     */
    private $__value = null;

    /**
     * @var string $amountCurrencyIdentifier
     */
    private $amountCurrencyIdentifier = null;

    /**
     * Construct
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value($value);
    }

    /**
     * Gets or sets the inner value
     *
     * @param string $value
     * @return string
     */
    public function value()
    {
        if ($args = func_get_args()) {
            $this->__value = $args[0];
        }
        return $this->__value;
    }

    /**
     * Gets a string value
     *
     * @return string
     */
    public function __toString()
    {
        return strval($this->__value);
    }

    /**
     * Gets as amountCurrencyIdentifier
     *
     * @return string
     */
    public function getAmountCurrencyIdentifier()
    {
        return $this->amountCurrencyIdentifier;
    }

    /**
     * Sets a new amountCurrencyIdentifier
     *
     * @param string $amountCurrencyIdentifier
     * @return self
     */
    public function setAmountCurrencyIdentifier($amountCurrencyIdentifier)
    {
        $this->amountCurrencyIdentifier = $amountCurrencyIdentifier;
        return $this;
    }
}

