<?php

namespace App\Entity;

use App\Repository\BulletinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BulletinRepository::class)]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $matiere = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $semestre = null;
    
    #[ORM\ManyToOne(inversedBy: 'bulletins')]
#[ORM\JoinColumn(name: 'idE', referencedColumnName: 'id', onDelete: 'CASCADE')]
private ?Etudiant $etudiant = null;




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatiere(): ?string
    {
        return $this->matiere;
    }

    public function setMatiere(string $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getSemestre(): ?string
    {
        return $this->semestre;
    }

    public function setSemestre(?string $semestre): static
    {
        $this->semestre = $semestre;

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
