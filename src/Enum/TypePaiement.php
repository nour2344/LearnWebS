<?php

namespace App\Enum;

enum TypePaiement: string
{
    case ESPECES = 'Espèces';
    case CHEQUE = 'Chèque';
    case VIREMENT = 'Virement';

    public function label(): string
    {
        return match ($this) {
            self::ESPECES => 'Espèces',
            self::CHEQUE => 'Chèque',
            self::VIREMENT => 'Virement',
        };
    }
}
