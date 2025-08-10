<?php

namespace App\Entity;
use App\Enum\CategorieDepense;

use App\Repository\DepenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseRepository::class)]
class Depense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: CategorieDepense::class)]
private ?CategorieDepense $categorie = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $montantD = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateD = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

   public function getCategorie(): ?CategorieDepense
{
    return $this->categorie;
}

public function setCategorie(?CategorieDepense $categorie): static
{
    $this->categorie = $categorie;
    return $this;
}

    public function getMontantD(): ?string
    {
        return $this->montantD;
    }

    public function setMontantD(string $montantD): static
    {
        $this->montantD = $montantD;

        return $this;
    }

    public function getDateD(): ?\DateTime
    {
        return $this->dateD;
    }

    public function setDateD(?\DateTime $dateD): static
    {
        $this->dateD = $dateD;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
