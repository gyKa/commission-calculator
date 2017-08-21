<?php

namespace Service\Currency;

use Exception\UndefinedCurrencyException;

class ExchangeService
{
    /**
     * @var RatesService
     */
    private $rates;

    /**
     * @var string
     */
    private $defaultCurrency;

    /**
     * @param RatesService $rates
     * @param string $defaultCurrency
     */
    public function __construct(RatesService $rates, string $defaultCurrency)
    {
        $this->rates = $rates;
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * @param string $currency
     * @throws UndefinedCurrencyException
     * @return float
     */
    public function getCurrencyRate(string $currency) : float
    {
        $rates = $this->rates->getRates();

        if (isset($rates[$currency])) {
            return $rates[$currency];
        }

        throw new UndefinedCurrencyException(
            sprintf('Currency "%s" is not found.', $currency)
        );
    }

    public function calculateRate($amount, $toCurrency, $fromCurrency = null) : float
    {
        if (!isset($fromCurrency)) {
            $fromCurrency = $this->defaultCurrency;
        }

        if ($this->rates->getBaseCurrency() !== $fromCurrency) {
            $amount = $amount / $this->getCurrencyRate($fromCurrency);
        }

        if ($toCurrency === $this->rates->getBaseCurrency()) {
            return $amount;
        }

        return $amount * $this->getCurrencyRate($toCurrency);
    }
}
