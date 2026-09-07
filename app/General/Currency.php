<?php

namespace App\General;

enum Currency: String
{
    case USDT = 'USDT';
    case USD  = 'USD';
    case EUR  = 'EUR';
    case COP  = 'COP';
    case VES  = 'VES';
}