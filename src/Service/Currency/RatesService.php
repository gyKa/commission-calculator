<?php

namespace Service\Currency;

class RatesService
{
    /**
     * @return array
     */
    public function getRates() : array
    {
        return [
            'EUR' => 1,
            'USD' => 1.1497,
            'JPY' => 129.53,
        ];
    }

    /**
     * @return string
     */
    public function getBaseCurrency() : string
    {
        return 'EUR';
    }
}
