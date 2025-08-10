<?php

namespace App\Entity;
use App\Enum\SourceRecette;

use App\Repository\RecetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SourceRecette::class)]
private ?SourceRecette $source = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $montantR = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateR = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $noteR = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?SourceRecette
{
    return $this->source;
}

public function setSource(?SourceRecette $source): static
{
    $this->source = $source;
    return $this;
}


    public function getMontantR(): ?string
    {
        return $this->montantR;
    }

    public function setMontantR(string $montantR): static
    {
        $this->montantR = $montantR;

        return $this;
    }

    public function getDateR(): ?\DateTime
    {
        return $this->dateR;
    }

    public function setDateR(\DateTime $dateR): static
    {
        $this->dateR = $dateR;

        return $this;
    }

    public function getNoteR(): ?string
    {
        return $this->noteR;
    }

    public function setNoteR(?string $noteR): static
    {
        $this->noteR = $noteR;

        return $this;
    }
}
