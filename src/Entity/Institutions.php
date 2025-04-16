<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use App\Repository\InstitutionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[GetCollection(paginationEnabled: false,
    order: ['title' => 'ASC'],
)]
#[ORM\Entity(repositoryClass: InstitutionsRepository::class)]
class Institutions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
