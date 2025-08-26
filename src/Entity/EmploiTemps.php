<?php
namespace App\Entity;

use App\Repository\EmploiTempsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmploiTempsRepository::class)]
#[ORM\Table(name: 'emploi_temps')]
class EmploiTemps
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;

    #[ORM\Column(length:50)] #[Assert\NotBlank] private ?string $classe = null;
    #[ORM\Column(length:150, nullable:true)] private ?string $titre = null;

    #[ORM\Column(type:'date')] #[Assert\NotBlank]
    private ?\DateTimeInterface $effectiveFrom = null;

    #[ORM\Column(length:10)] #[Assert\Choice(['upload','manual'])]
    private string $mode = 'upload';

    #[ORM\Column(length:255, nullable:true)]
    private ?string $fileName = null;

    /** @var Collection<int,EmploiLigne> */
    #[ORM\OneToMany(mappedBy:'emploi', targetEntity:EmploiLigne::class, cascade:['persist'], orphanRemoval:true)]
    private Collection $lignes;

#[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    // getters/setters …
    public function getId(): ?int { return $this->id; }
    public function getClasse(): ?string { return $this->classe; }
    public function setClasse(string $c): self { $this->classe=$c; return $this; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $t): self { $this->titre=$t; return $this; }
    public function getEffectiveFrom(): ?\DateTimeInterface { return $this->effectiveFrom; }
    public function setEffectiveFrom(\DateTimeInterface $d): self { $this->effectiveFrom=$d; return $this; }
    public function getMode(): string { return $this->mode; }
    public function setMode(string $m): self { $this->mode=$m; return $this; }
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $f): self { $this->fileName=$f; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int,EmploiLigne> */ public function getLignes(): Collection { return $this->lignes; }
    public function addLigne(EmploiLigne $l): self { if(!$this->lignes->contains($l)){ $this->lignes->add($l); $l->setEmploi($this);} return $this; }
    public function removeLigne(EmploiLigne $l): self { if($this->lignes->removeElement($l) && $l->getEmploi()===$this){ $l->setEmploi(null);} return $this; }
}
