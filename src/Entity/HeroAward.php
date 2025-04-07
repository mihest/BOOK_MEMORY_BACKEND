<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HeroAwardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[GetCollection(
    paginationEnabled: false,
    order: ['title' => 'ASC'],
    normalizationContext: ['groups' => ['heroAward:read']]
)]
#[ApiFilter(SearchFilter::class, properties: ['category' => 'exact'])]
#[ORM\Entity(repositoryClass: HeroAwardRepository::class)]
class HeroAward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['heroAward:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['heroAward:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    #[Groups(['heroAward:read'])]
    private ?string $title = null;

    /**
     * @var Collection<int, ApplicationFormHeroAward>
     */
    #[ORM\OneToMany(targetEntity: ApplicationFormHeroAward::class, mappedBy: 'heroAward', cascade: ['all'])]
    private Collection $applicationFormHeroAwards;

    public function __construct()
    {
        $this->applicationFormHeroAwards = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->title ?? '/' . ' - ' . (string) $this->category ?? '/';
    }

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

    /**
     * @return Collection<int, ApplicationFormHeroAward>
     */
    public function getApplicationFormHeroAwards(): Collection
    {
        return $this->applicationFormHeroAwards;
    }

    public function addApplicationFormHeroAward(ApplicationFormHeroAward $applicationFormHeroAward): static
    {
        if (!$this->applicationFormHeroAwards->contains($applicationFormHeroAward)) {
            $this->applicationFormHeroAwards->add($applicationFormHeroAward);
            $applicationFormHeroAward->setHeroAward($this);
        }

        return $this;
    }

    public function removeApplicationFormHeroAward(ApplicationFormHeroAward $applicationFormHeroAward): static
    {
        if ($this->applicationFormHeroAwards->removeElement($applicationFormHeroAward)) {
            // set the owning side to null (unless already changed)
            if ($applicationFormHeroAward->getHeroAward() === $this) {
                $applicationFormHeroAward->setHeroAward(null);
            }
        }

        return $this;
    }
}
