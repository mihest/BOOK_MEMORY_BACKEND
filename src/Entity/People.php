<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Entity\Traits\CreatedAtTrait;
use App\Entity\Traits\UpdatedAtTrait;
use App\Filters\PeopleFullNameFilter;
use App\Repository\PeopleRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

enum PeopleType: string
{
    case RF = 'rf';
    case VOV = 'vov';
    case SVO = 'svo';
    case CHERNOBYL = 'chernobyl';
    case LOCAL = 'local';
}

#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[Get(normalizationContext: ['groups' => ['applicationForm:read']],)]
#[GetCollection(
    openapi: new Operation(
        parameters: [
            new Parameter(
                name: 'type',
                in: 'query',
                required: true,
                schema: [
                    'type' => 'string',
                    'enum' => [
                        'rf',
                        'vov',
                        'svo',
                        'chernobyl',
                        'local'
                    ]
                ]
            ),
            new Parameter(
                name: 'full_name',
                in: 'query',
                description: 'Поиск по полному имени (фамилия, имя, отчество)',
                required: false,
                schema: [
                    'type' => 'string'
                ]
            ),
        ]
    ),
    paginationEnabled: true,
    normalizationContext: ['groups' => ['applicationForm:read:minimal']],
)]
#[ApiFilter(PeopleFullNameFilter::class, properties: ['full_name' => 'partial'])]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]
#[ORM\Entity(repositoryClass: PeopleRepository::class)]
class People
{
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?string $patronymic = null;

    #[ORM\ManyToOne(targetEntity: MilitaryRanks::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?MilitaryRanks $militaryRank = null;

    #[ORM\Column(type: 'date',length: 255, nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?DateTimeInterface $birthDateAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $additional = null;

    #[ORM\Column(type: Types::ENUM, enumType: PeopleType::class)]
    #[Groups('applicationForm:read')]
    private ?PeopleType $type = null;

    #[ORM\Column(type: 'date',length: 255, nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?DateTimeInterface $deathDateAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?string $city = null;

    #[Vich\UploadableField(mapping: 'people_images', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['applicationForm:read', 'applicationForm:read:minimal'])]
    private ?string $image = null;

    /**
     * @var Collection<int, PeopleArchive>
     */
    #[ORM\OneToMany(targetEntity: PeopleArchive::class, mappedBy: 'people', cascade: ['persist'])]
    #[Groups('applicationForm:read')]
    private Collection $archive;

    /**
     * @var Collection<int, HeroAward>
     */
    #[ORM\ManyToMany(targetEntity: HeroAward::class, inversedBy: 'peoples')]
    #[Groups('applicationForm:read')]
    private Collection $heroAwards;

    public function __construct()
    {
        $this->archive = new ArrayCollection();
        $this->heroAwards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): ?static
    {
        $this->id = $id;
        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): static
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getFullName(): string
    {
        return implode(' ', array_filter([
            $this->getSurname(),
            $this->getName(),
            $this->getPatronymic(),
        ]));
    }

    public function getMilitaryRank(): ?MilitaryRanks
    {
        return $this->militaryRank;
    }

    public function setMilitaryRank(?MilitaryRanks $militaryRank): static
    {
        $this->militaryRank = $militaryRank;

        return $this;
    }

    public function getBirthDateAt(): ?DateTimeInterface
    {
        return $this->birthDateAt;
    }

    public function setBirthDateAt(?DateTimeInterface $birthDateAt): static
    {
        $this->birthDateAt = $birthDateAt;

        return $this;
    }

    public function getAdditional(): ?string
    {
        return strip_tags(html_entity_decode($this->additional));
    }

    public function setAdditional(?string $additional): static
    {
        $this->additional = $additional;

        return $this;
    }

    public function getType(): ?PeopleType
    {
        return $this->type;
    }

    public function setType(PeopleType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDeathDateAt(): ?DateTimeInterface
    {
        return $this->deathDateAt;
    }

    public function setDeathDateAt(?DateTimeInterface $deathDateAt): static
    {
        $this->deathDateAt = $deathDateAt;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }
    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }
    public function getImage(): ?string
    {
        return $this->image;
    }
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }


    /**
     * @return Collection<int, PeopleArchive>
     */
    public function getArchive(): Collection
    {
        return $this->archive;
    }

    public function addArchive(PeopleArchive $archive): static
    {
        if (!$this->archive->contains($archive)) {
            $this->archive->add($archive);
            $archive->setPeople($this);
        }

        return $this;
    }

    public function removeArchive(PeopleArchive $archive): static
    {
        if ($this->archive->removeElement($archive)) {
            // set the owning side to null (unless already changed)
            if ($archive->getPeople() === $this) {
                $archive->setPeople(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HeroAward>
     */
    public function getHeroAwards(): Collection
    {
        return $this->heroAwards;
    }

    public function addHeroAward(HeroAward $heroAward): static
    {
        if (!$this->heroAwards->contains($heroAward)) {
            $this->heroAwards->add($heroAward);
        }
        return $this;
    }

    public function removeHeroAward(HeroAward $heroAward): static
    {
        $this->heroAwards->removeElement($heroAward);
        return $this;
    }

    public function getHeroAwardAll(): string
    {
        $heroAwards = $this->heroAwards;
        $arr = [];
        $i = 1;

        foreach ($heroAwards as $heroAward)
        {
            $arr[] = "$i) Награда - " . ($heroAward->getTitle() ?? 'Нет');
            $arr[] = 'Год - ' . ($heroAward->getYearAt() ?? 'Нет');
            $arr[] = 'Описание - ' . ($heroAward->getDescription() ?? 'Нет');
            $i++;
        }

        return implode("<br>", $arr);
    }
}