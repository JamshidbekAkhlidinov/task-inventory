<?php

namespace App\Enums;

enum ProviderRefundStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
}
