<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MilitaryRanksRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[GetCollection(
    paginationEnabled: false,
    order: ['title' => 'ASC'],
    normalizationContext: ['groups' => ['militaryRanks:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['category' => 'exact'])]
#[ORM\Entity(repositoryClass: MilitaryRanksRepository::class)]
class MilitaryRanks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['militaryRanks:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['militaryRanks:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    #[Groups(['militaryRanks:read'])]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
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
