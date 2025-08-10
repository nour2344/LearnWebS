<?php

namespace App\Entity;
use App\Entity\Personnel;

use App\Repository\SalaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
class Salaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $mois = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateP = null;

   #[ORM\ManyToOne(targetEntity: Personnel::class)]
#[ORM\JoinColumn(name: "idP", referencedColumnName: "id")]
private ?Personnel $personnel = null;

// src/Entity/Salaire.php
#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $estPaye = false;

public function isEstPaye(): bool { return $this->estPaye; }
public function setEstPaye(bool $estPaye): static { $this->estPaye = $estPaye; return $this; }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMois(): ?string
    {
        return $this->mois;
    }

    public function setMois(string $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateP(): ?\DateTime
    {
        return $this->dateP;
    }

    public function setDateP(?\DateTime $dateP): static
    {
        $this->dateP = $dateP;

        return $this;
    }

    public function getPersonnel(): ?Personnel
{
    return $this->personnel;
}

public function setPersonnel(?Personnel $personnel): static
{
    $this->personnel = $personnel;

    return $this;
}

}
