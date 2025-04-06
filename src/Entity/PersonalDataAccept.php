<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use App\Repository\PersonalDataAcceptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[Get(
    uriTemplate: 'personal_data_accepts',
    paginationEnabled: false,
)]
#[ORM\Entity(repositoryClass: PersonalDataAcceptRepository::class)]
class PersonalDataAccept
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
