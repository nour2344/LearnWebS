<?php

namespace App\Entity;

use App\Entity\ParentProfile;
use App\Repository\EtudiantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
class Etudiant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La classe est obligatoire.')]
    #[Assert\Length(min: 1, max: 50)]
    private ?string $classe = null;

    #[ORM\Column(name: 'dateN', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\LessThan('today', message: 'La date de naissance doit être antérieure à aujourd\'hui.')]
    private ?\DateTimeInterface $dateN = null;

    #[ORM\Column(name: 'dateInscription', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date d\'inscription est obligatoire.')]
    #[Assert\LessThanOrEqual('today', message: 'La date d\'inscription ne peut pas être dans le futur.')]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: 'string', length: 15)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d{8,15}$/', message: 'Le numéro doit contenir entre 8 et 15 chiffres.')]
    private ?string $numTel = null;

    /** Numéro supplémentaire – optionnel */
    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    #[Assert\Regex(pattern: '/^\d{8,15}$/', message: 'Le numéro doit contenir entre 8 et 15 chiffres.')]
    private ?string $numTel2 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dernierPaiement = null;

    #[ORM\ManyToMany(targetEntity: ParentProfile::class, mappedBy: 'children')]
    private Collection $parents;

    #[ORM\Column(type: 'boolean')]
    private bool $bulletinsVisibles = false;

    /** Nom du père – optionnel */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nomPere = null;

    /** Nom de la mère – optionnel */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nomMere = null;

    public function __construct()
    {
        $this->parents = new ArrayCollection();
    }

    // ---------- Getters/Setters ----------

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getClasse(): ?string { return $this->classe; }
    public function setClasse(string $classe): self { $this->classe = $classe; return $this; }

    public function getDateN(): ?\DateTimeInterface { return $this->dateN; }
    public function setDateN(\DateTimeInterface $dateN): self { $this->dateN = $dateN; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }

    public function getNumTel(): ?string { return $this->numTel; }
    public function setNumTel(string $numTel): self { $this->numTel = $numTel; return $this; }

    public function getNumTel2(): ?string { return $this->numTel2; }
    public function setNumTel2(?string $numTel2): self { $this->numTel2 = $numTel2; return $this; }

    public function getDernierPaiement(): ?\DateTimeInterface { return $this->dernierPaiement; }
    public function setDernierPaiement(?\DateTimeInterface $dernierPaiement): self { $this->dernierPaiement = $dernierPaiement; return $this; }

    /** @return Collection<int, ParentProfile> */
    public function getParents(): Collection { return $this->parents; }

    public function isBulletinsVisibles(): bool { return $this->bulletinsVisibles; }
    public function setBulletinsVisibles(bool $visible): self { $this->bulletinsVisibles = $visible; return $this; }

    public function getNomPere(): ?string { return $this->nomPere; }
    public function setNomPere(?string $v): self { $this->nomPere = $v; return $this; }

    public function getNomMere(): ?string { return $this->nomMere; }
    public function setNomMere(?string $v): self { $this->nomMere = $v; return $this; }
}