<?php

namespace App\Entity;

use App\Repository\ApplicationFormImagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationFormImagesRepository::class)]
class ApplicationFormImages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?ApplicationForm $applicationForm = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

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
}
