<?php

namespace App\Enum;

enum CategorieDepense: string
{
    case LOYER = 'Loyer';
    case ELECTRICITE = 'Électricité';
        case EAU = 'Eau';
            case NETTOYAGE = 'Nettoyage';


    case INTERNET = 'Internet';
    case FOURNITURES = 'Fournitures';
    case SECURITE = 'Securite';

    case AUTRE = 'Autre';

    public function label(): string
{
    return match($this) {
        self:: LOYER  => 'Loyer',
        self::ELECTRICITE  => 'Électricité ',
        self::EAU => 'Eau',
        self::NETTOYAGE => 'Nettoyage',
        self::INTERNET => 'Internet',
        self::FOURNITURES => 'Fournitures',
        self::SECURITE => 'Securite',

        self::AUTRE => 'Autre',

    };
}
}
