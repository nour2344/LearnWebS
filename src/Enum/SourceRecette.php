<?php

namespace App\Enum;

enum SourceRecette: string
{
        case DON= 'Don';

    case VENTE = 'Vente';
    case INSCRIPTION= 'Frais inscription';
        case LOCATION= 'Location';

    case AUTRE = 'Autre';

    public function label(): string
{
    return match($this) {
                self::DON => 'Don',

        self::VENTE  => 'Vente',
                        self::INSCRIPTION => 'Frais inscription',

                                        self::LOCATION => 'Location',

        self::AUTRE  => ' Autre',
    };
}
}
