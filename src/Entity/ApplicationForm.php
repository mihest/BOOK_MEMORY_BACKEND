<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Api\ApplicationForm\AddToApplicationFormController;
use App\Entity\Traits\CreatedAtTrait;
use App\Entity\Traits\UpdatedAtTrait;
use App\Repository\ApplicationFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\OpenApi\Model;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[Post(
    uriTemplate: 'application_forms/add',
    controller: AddToApplicationFormController::class,
    openapi: new Model\Operation(
        requestBody: new Model\RequestBody(
            content: new \ArrayObject([
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'images[]' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'format' => 'binary'
                                ],
                            ],

                            'archive[]' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'format' => 'binary'
                                ],
                            ],
                            'surname' => [
                                'type' => 'string',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                            'patronymic' => [
                                'type' => 'string',
                            ],
                            'city' => [
                                'type' => 'string',
                            ],
                            'category' => [
                                'type' => 'string',
                            ],
                            'militaryRank' => [
                                'type' => 'string',
                            ],
                            'birthDateAt' => [
                                'type' => 'string',
                            ],
                            'deathDateAt' => [
                                'type' => 'string',
                            ],
                            'additional' => [
                                'type' => 'string',
                            ],
                            'surnameSender' => [
                                'type' => 'string',
                            ],
                            'nameSender' => [
                                'type' => 'string',
                            ],
                            'patronymicSender' => [
                                'type' => 'string',
                            ],
                            'phone' => [
                                'type' => 'string',
                            ],
                            'heroAward' => [
                                'type' => 'string',
                                'example' => '[{"title":1,"yearAt":"1945","description":"Орден Победы"},{"title":2,"yearAt":"1946","description":"Медаль"}]',
                                'description' => 'JSON-массив объектов с id, yearAt и description',
                            ],
                        ],
                    ]
                ]
            ])
        )
    ),
    deserialize: false
)]
#[GetCollection(normalizationContext: ['groups' => ['applicationForm:read']])]
#[Post(
    uriTemplate: 'application_forms/edit',
    controller: AddToApplicationFormController::class,
    openapi: new Model\Operation(
        requestBody: new Model\RequestBody(
            content: new \ArrayObject([
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                        ],
                    ]
                ]
            ])
        )
    ),
    deserialize: false)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
#[ORM\Entity(repositoryClass: ApplicationFormRepository::class)]
class ApplicationForm
{
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('applicationForm:read')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $patronymic = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $militaryRank = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $birthDateAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $additional = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $surnameSender = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $nameSender = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $patronymicSender = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $deathDateAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $city = null;

    /**
     * @var Collection<int, ApplicationFormImages>
     */
    #[ORM\OneToMany(targetEntity: ApplicationFormImages::class, mappedBy: 'applicationForm', cascade: ['all'])]
    #[Groups('applicationForm:read')]
    private Collection $images;

    /**
     * @var Collection<int, ApplicationFormArchive>
     */
    #[ORM\OneToMany(targetEntity: ApplicationFormArchive::class, mappedBy: 'applicationForm', cascade: ['all'])]
    #[Groups('applicationForm:read')]
    private Collection $archive;

    /**
     * @var Collection<int, ApplicationFormHeroAward>
     */
    #[ORM\OneToMany(targetEntity: ApplicationFormHeroAward::class, mappedBy: 'applicationForm', cascade: ['all'])]
    #[Groups('applicationForm:read')]
    private Collection $heroAward;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[Groups('applicationForm:read')]
    private ?string $institute = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->archive = new ArrayCollection();
        $this->heroAward = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMilitaryRank(): ?string
    {
        return $this->militaryRank;
    }

    public function setMilitaryRank(?string $militaryRank): static
    {
        $this->militaryRank = $militaryRank;

        return $this;
    }

    public function getBirthDateAt(): ?string
    {
        return $this->birthDateAt;
    }

    public function setBirthDateAt(?string $birthDateAt): static
    {
        $this->birthDateAt = $birthDateAt;

        return $this;
    }

    public function getAdditional(): ?string
    {
        return $this->additional;
    }

    public function setAdditional(?string $additional): static
    {
        $this->additional = $additional;

        return $this;
    }

    public function getSurnameSender(): ?string
    {
        return $this->surnameSender;
    }

    public function setSurnameSender(string $surnameSender): static
    {
        $this->surnameSender = $surnameSender;

        return $this;
    }

    public function getNameSender(): ?string
    {
        return $this->nameSender;
    }

    public function setNameSender(string $nameSender): static
    {
        $this->nameSender = $nameSender;

        return $this;
    }

    public function getPatronymicSender(): ?string
    {
        return $this->patronymicSender;
    }

    public function setPatronymicSender(?string $patronymicSender): static
    {
        $this->patronymicSender = $patronymicSender;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
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

    public function getDeathDateAt(): ?string
    {
        return $this->deathDateAt;
    }

    public function setDeathDateAt(?string $deathDateAt): static
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

    /**
     * @return Collection<int, ApplicationFormImages>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ApplicationFormImages $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setApplicationForm($this);
        }

        return $this;
    }

    public function removeImage(ApplicationFormImages $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getApplicationForm() === $this) {
                $image->setApplicationForm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApplicationFormArchive>
     */
    public function getArchive(): Collection
    {
        return $this->archive;
    }

    public function addArchive(ApplicationFormArchive $archive): static
    {
        if (!$this->archive->contains($archive)) {
            $this->archive->add($archive);
            $archive->setApplicationForm($this);
        }

        return $this;
    }

    public function removeArchive(ApplicationFormArchive $archive): static
    {
        if ($this->archive->removeElement($archive)) {
            // set the owning side to null (unless already changed)
            if ($archive->getApplicationForm() === $this) {
                $archive->setApplicationForm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApplicationFormHeroAward>
     */
    public function getHeroAward(): Collection
    {
        return $this->heroAward;
    }

    public function addHeroAward(ApplicationFormHeroAward $heroAward): static
    {
        if (!$this->heroAward->contains($heroAward)) {
            $this->heroAward->add($heroAward);
            $heroAward->setApplicationForm($this);
        }

        return $this;
    }

    public function removeHeroAward(ApplicationFormHeroAward $heroAward): static
    {
        if ($this->heroAward->removeElement($heroAward)) {
            // set the owning side to null (unless already changed)
            if ($heroAward->getApplicationForm() === $this) {
                $heroAward->setApplicationForm(null);
            }
        }

        return $this;
    }

    public function getHeroAwardAll(): string
    {
        $heroAwards = $this->heroAward;
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

    public function getSender(): string
    {
        $arr[] = "Фамилия - " . ($this->getSurnameSender() ?? 'Нет');
        $arr[] = 'Имя - ' . ($this->getNameSender() ?? 'Нет');
        $arr[] = 'Телефон - ' . ($this->getPhone() ?? 'Нет');

        return implode("<br>", $arr);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMedia(): string
    {
        $arr = [];
        $i = 1;

        foreach ($this->getArchive() as $value)
        {
            $link = 'http://book-memory-new.itlabs.top/media/application_form/' . $value->getMedia();

            $arr[] = "<a href=\"$link\" target=\"_blank\">Медиа - $i</a>";

            $i++;
        }

        return implode("<br>", $arr);
    }

    public function getInstitute(): ?string
    {
        return $this->institute;
    }

    public function setInstitute(string $institute): static
    {
        $this->institute = $institute;

        return $this;
    }
}
