<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case PROVIDER_REFUND = 'provider_refund';
    case CLIENT_REFUND = 'client_refund';
}
