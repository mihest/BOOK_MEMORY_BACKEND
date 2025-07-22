<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Repository\HeroAwardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[GetCollection(
    paginationEnabled: false,
    order: ['title' => 'ASC'],
    normalizationContext: ['groups' => ['heroAward:read']]
)]
#[ORM\Entity(repositoryClass: HeroAwardRepository::class)]
class HeroAward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['heroAward:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['heroAward:read', 'applicationForm:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['heroAward:read', 'applicationForm:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['heroAward:read', 'applicationForm:read'])]
    private ?int $yearAt = null;

    /**
     * @var Collection<int, People>
     */
    #[ORM\ManyToMany(targetEntity: People::class, mappedBy: 'heroAwards')]
    private Collection $peoples;

    public function __construct()
    {
        $this->peoples = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getYearAt(): ?int
    {
        return $this->yearAt;
    }

    public function setYearAt(?int $yearAt): static
    {
        $this->yearAt = $yearAt;

        return $this;
    }

    /**
     * @return Collection<int, People>
     */
    public function getPeoples(): Collection
    {
        return $this->peoples;
    }
}
