<?php

namespace App\Enums;

enum ClientRefundStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
}
