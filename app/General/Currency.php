<?php

namespace App\General;

enum Currency: String
{
    case USD  = 'USD';
    case EUR  = 'EUR';
    case COP  = 'COP';
    case USDT = 'USDT';
    case VES  = 'VES';
}