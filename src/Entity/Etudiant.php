<?php

namespace App\Entity;

use App\Repository\EtudiantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
class Etudiant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $classe = null;

    #[ORM\Column(name: 'dateN', type: 'date')]
private ?\DateTimeInterface $dateN = null;



    #[ORM\Column(name: 'dateInscription', type: 'date')]
private ?\DateTimeInterface $dateInscription = null;


#[ORM\Column(type: 'string', length: 15)]
private ?string $numTel = null;


  


#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $dernierPaiement = null;

public function getDernierPaiement(): ?\DateTimeInterface
{
    return $this->dernierPaiement;
}

public function setDernierPaiement(?\DateTimeInterface $dernierPaiement): static
{
    $this->dernierPaiement = $dernierPaiement;
    return $this;
}



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getClasse(): ?string
    {
        return $this->classe;
    }

    public function setClasse(string $classe): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getDateN(): ?\DateTime
    {
        return $this->dateN;
    }

    public function setDateN(\DateTime $dateN): static
    {
        $this->dateN = $dateN;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
{
    return $this->dateInscription;
}

public function setDateInscription(?\DateTime $dateInscription): static
{
    $this->dateInscription = $dateInscription;

    return $this;
}

public function getNumTel(): ?string
{
    return $this->numTel;
}

public function setNumTel(?string $numTel): self
{
    $this->numTel = $numTel;
    return $this;
}



  

}
