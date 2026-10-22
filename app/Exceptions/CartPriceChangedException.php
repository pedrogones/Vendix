<?php

namespace App\Exceptions;

class CartPriceChangedException extends \DomainException
{
    public function __construct(public readonly array $prices)
    {
        parent::__construct('Os preços mudaram. Confira o novo resumo e confirme novamente.');
    }
}
