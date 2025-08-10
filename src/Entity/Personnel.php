<?php

namespace App\Entity;
use App\Enum\RolePersonnel;

use App\Repository\PersonnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonnelRepository::class)]
class Personnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomP = null;

    #[ORM\Column(length: 255)]
    private ?string $prenomP = null;

    #[ORM\Column(type: 'string', enumType: RolePersonnel::class)]
private RolePersonnel $role;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $salaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateRecrutement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomP(): ?string
    {
        return $this->nomP;
    }

    public function setNomP(string $nomP): static
    {
        $this->nomP = $nomP;

        return $this;
    }

    public function getPrenomP(): ?string
    {
        return $this->prenomP;
    }

    public function setPrenomP(string $prenomP): static
    {
        $this->prenomP = $prenomP;

        return $this;
    }

    public function getRole(): RolePersonnel
{
    return $this->role;
}

public function setRole(RolePersonnel $role): static
{
    $this->role = $role;

    return $this;
}


    public function getSalaire(): ?string
    {
        return $this->salaire;
    }

    public function setSalaire(string $salaire): static
    {
        $this->salaire = $salaire;

        return $this;
    }

    public function getDateRecrutement(): ?\DateTime
    {
        return $this->dateRecrutement;
    }

    public function setDateRecrutement(?\DateTime $dateRecrutement): static
    {
        $this->dateRecrutement = $dateRecrutement;

        return $this;
    }
}
