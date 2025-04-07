<?php

namespace App\Entity;

use App\Repository\ApplicationFormHeroAwardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationFormHeroAwardRepository::class)]
class ApplicationFormHeroAward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[ORM\ManyToOne(inversedBy: 'applicationFormHeroAwards')]
    private ?HeroAward $heroAward = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $yearAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'heroAward')]
    private ?ApplicationForm $applicationForm = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeroAward(): ?HeroAward
    {
        return $this->heroAward;
    }

    public function setHeroAward(?HeroAward $heroAward): static
    {
        $this->heroAward = $heroAward;

        return $this;
    }

    public function getYearAt(): ?string
    {
        return $this->yearAt;
    }

    public function setYearAt(?string $yearAt): static
    {
        $this->yearAt = $yearAt;

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

    public function getApplicationForm(): ?ApplicationForm
    {
        return $this->applicationForm;
    }

    public function setApplicationForm(?ApplicationForm $applicationForm): static
    {
        $this->applicationForm = $applicationForm;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
