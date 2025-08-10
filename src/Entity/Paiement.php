<?php

namespace App\Entity;
use App\Entity\Etudiant;
use App\Enum\TypePaiement;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: TypePaiement::class)]
private ?TypePaiement $typePaiement = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $montantP = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateP = null;

   

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne(targetEntity: Etudiant::class)]
#[ORM\JoinColumn(name: "idE", referencedColumnName: "id", nullable: false)]
private ?Etudiant $etudiant = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypePaiement(): ?TypePaiement
{
    return $this->typePaiement;
}

public function setTypePaiement(?TypePaiement $typePaiement): static
{
    $this->typePaiement = $typePaiement;
    return $this;
}


    public function getMontantP(): ?string
    {
        return $this->montantP;
    }

    public function setMontantP(string $montantP): static
    {
        $this->montantP = $montantP;

        return $this;
    }

    public function getDateP(): ?\DateTime
    {
        return $this->dateP;
    }

    public function setDateP(\DateTime $dateP): static
    {
        $this->dateP = $dateP;

        return $this;
    }

    

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getEtudiant(): ?Etudiant
{
    return $this->etudiant;
}

public function setEtudiant(?Etudiant $etudiant): static
{
    $this->etudiant = $etudiant;
    return $this;
}

}
