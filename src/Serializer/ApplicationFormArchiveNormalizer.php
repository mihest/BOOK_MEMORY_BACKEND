<?php

namespace App\Serializer;

use App\Entity\ApplicationFormArchive;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

readonly class ApplicationFormArchiveNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private StorageInterface    $storage
    ) {
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        /* @var ApplicationFormArchive $object */
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['media'] = $this->storage->resolveUri($object, 'mediaFile');

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof ApplicationFormArchive;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ApplicationFormArchive::class => true,
        ];
    }
}