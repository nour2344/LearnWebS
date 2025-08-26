<?php
namespace App\Entity;
use App\Repository\EmploiLigneRepository;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmploiLigneRepository::class)]
#[ORM\Table(name: 'emploi_ligne')]
class EmploiLigne
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id=null;

    #[ORM\ManyToOne(inversedBy:'lignes')] #[ORM\JoinColumn(nullable:false)]
    private ?EmploiTemps $emploi = null;

    #[ORM\Column(length:10)] #[Assert\Choice(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])]
    private string $day;

    #[ORM\Column(type:'time')] #[Assert\NotBlank] private \DateTimeInterface $startAt;
    #[ORM\Column(type:'time')] #[Assert\NotBlank] private \DateTimeInterface $endAt;

    #[ORM\Column(length:120)] #[Assert\NotBlank] private string $matiere;

    // getters/setters …
    public function getId(): ?int { return $this->id; }
    public function getEmploi(): ?EmploiTemps { return $this->emploi; }
    public function setEmploi(?EmploiTemps $e): self { $this->emploi=$e; return $this; }
    public function getDay(): string { return $this->day; }
    public function setDay(string $d): self { $this->day=$d; return $this; }
    public function getStartAt(): \DateTimeInterface { return $this->startAt; }
    public function setStartAt(\DateTimeInterface $t): self { $this->startAt=$t; return $this; }
    public function getEndAt(): \DateTimeInterface { return $this->endAt; }
    public function setEndAt(\DateTimeInterface $t): self { $this->endAt=$t; return $this; }
    public function getMatiere(): string { return $this->matiere; }
    public function setMatiere(string $m): self { $this->matiere=$m; return $this; }
}
