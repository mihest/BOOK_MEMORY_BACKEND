<?php

namespace App\Serializer;

use App\Entity\ApplicationForm;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

readonly class ApplicationFormNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private StorageInterface    $storage
    ) {
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        /* @var ApplicationForm $object */
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['qr'] = $this->storage->resolveUri($object, 'qrFile');

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof ApplicationForm;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ApplicationForm::class => true,
        ];
    }
}