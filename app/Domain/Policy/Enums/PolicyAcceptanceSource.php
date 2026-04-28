<?php

namespace App\Domain\Policy\Enums;

enum PolicyAcceptanceSource: string
{
    case Registration = 'registration';
    case ReAcceptanceGate = 're_acceptance_gate';
    case Settings = 'settings';
    case Checkout = 'checkout';
    case ManualAdmin = 'manual_admin';
}
