<?php

namespace App\Entity;

use App\Entity\Traits\CreatedAtTrait;
use App\Entity\Traits\UpdatedAtTrait;
use App\Repository\PeopleArchiveRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: PeopleArchiveRepository::class)]
class PeopleArchive
{
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('applicationForm:read')]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Поле не может быть пустым.')]
    #[Groups('applicationForm:read')]
    private ?string $media = null;

    #[Assert\NotBlank(message: 'Заголовок не может быть пустым.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Заголовок не должен превышать {{ limit }} символов.'
    )]
    #[ORM\Column(length: 255, nullable: false)]
    #[Groups('applicationForm:read')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('applicationForm:read')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'archive')]
    private ?People $people = null;
    
    #[Assert\NotNull(message: 'Файл обязателен.')]
    #[Vich\UploadableField(mapping: 'people_media', fileNameProperty: 'media')]
    #[Assert\File(
        maxSize: '20M',
        mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'],
        mimeTypesMessage: 'Поддерживаются только: JPG, JPEG, PNG, WEBP',
        maxSizeMessage: 'Файл слишком большой (максимум 20 МБ).'
    )]
    private ?File $mediaFile = null;

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

    public function getPeople(): ?People
    {
        return $this->people;
    }

    public function setPeople(?People $people): static
    {
        $this->people = $people;

        return $this;
    }

    public function getMediaFile(): ?File
    {
        return $this->mediaFile;
    }

    public function setMediaFile(?File $mediaFile): self
    {
        $this->mediaFile = $mediaFile;
        if (null !== $mediaFile) {
            $this->updatedAt = new DateTime();
        }

        return $this;
    }
}
