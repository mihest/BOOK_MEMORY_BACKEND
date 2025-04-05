<?php

namespace App\Entity;

use App\Repository\ApplicationFormArchiveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationFormArchiveRepository::class)]
class ApplicationFormArchive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $media = null;

    #[ORM\ManyToOne(inversedBy: 'archive')]
    private ?ApplicationForm $applicationForm = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedia(): ?string
    {
        return $this->media;
    }

    public function setMedia(?string $media): static
    {
        $this->media = $media;

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
