<?php

namespace App\Entity;

use App\Repository\ParentProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParentProfileRepository::class)]
#[ORM\Table(name: 'parent_profile')]
#[ORM\UniqueConstraint(name: 'uniq_parent_user', columns: ['user_id'])]
class ParentProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Owning side of the 1-1 (has the foreign key)
    #[ORM\OneToOne(inversedBy: 'parentProfile', cascade: ['persist','remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 150)]
    private string $fullName;

    #[ORM\Column(length: 30)]
    private string $phone;

    #[ORM\ManyToMany(targetEntity: Etudiant::class, inversedBy: 'parents')]
    #[ORM\JoinTable(name: 'parent_etudiant')]
    #[ORM\JoinColumn(name: 'parent_profile_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'etudiant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getFullName(): string { return $this->fullName; }
    public function setFullName(string $fullName): self { $this->fullName = $fullName; return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }

    /** @return Collection<int, Etudiant> */
    public function getChildren(): Collection { return $this->children; }

    public function addChild(Etudiant $etudiant): self
    {
        if (!$this->children->contains($etudiant)) {
            $this->children->add($etudiant);
        }
        return $this;
    }

    public function removeChild(Etudiant $etudiant): self
    {
        $this->children->removeElement($etudiant);
        return $this;
    }
}
