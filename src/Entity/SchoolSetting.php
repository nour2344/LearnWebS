<?php
namespace App\Entity;

use App\Repository\SchoolSettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SchoolSettingRepository::class)]
class SchoolSetting
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 7, options: ['default' => '#6366f1'])]
    #[Assert\Regex('/^#([0-9a-fA-F]{3}){1,2}$/')]
    private ?string $primaryColor = '#6366f1';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoFilename = null; // stored under public/uploads/logos

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getPrimaryColor(): ?string { return $this->primaryColor; }
    public function setPrimaryColor(?string $primaryColor): self { $this->primaryColor = $primaryColor; return $this; }

    public function getLogoFilename(): ?string { return $this->logoFilename; }
    public function setLogoFilename(?string $logoFilename): self { $this->logoFilename = $logoFilename; return $this; }
}
