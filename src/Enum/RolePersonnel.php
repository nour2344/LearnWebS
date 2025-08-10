<?php

namespace App\Enum;

enum RolePersonnel: string
{
    case PROFESSEUR = 'Professeur';
    case DIRECTEUR = 'Directeur';
    case Administrateur = 'Administrateur';
    case SECRETAIRE = 'Secrétaire';
        case SURVEILLANT = 'Surveillant';


    public function label(): string
{
    return match($this) {
        self::Administrateur => '🛠Administrateur',
        self::PROFESSEUR  => '🧑‍🏫 PROFESSEUR ',
        self::DIRECTEUR => '👑 Directeur',
         self:: SECRETAIRE => 'Secrétaire',
                 self::SURVEILLANT => 'Surveillant',


    };
}

}

