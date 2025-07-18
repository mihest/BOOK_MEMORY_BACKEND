<?php

namespace App\Form;

use App\Entity\PeopleArchive;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PeopleArchiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mediaFile', VichFileType::class, [
                'label' => 'Медиа',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => true,
                'help' => '
                    <div class="mt-2">
                        <strong>Допустимые форматы:</strong><br>
                        <span class="badge badge-info">*.jpg</span>
                        <span class="badge badge-info">*.jpeg</span>
                        <span class="badge badge-info">*.png</span>
                        <span class="badge badge-info">*.webp</span>
                        <span class="badge badge-info">*.mp4</span>
                        <span class="badge badge-info">*.webm</span>
                        <span class="badge badge-info">*.docx</span>
                        <span class="badge badge-info">*.pdf</span>
                    </div>
                ',
                'constraints' => [
                    new File([
                        'maxSize' => '20M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'video/mp4',
                            'video/webm',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите файл в формате JPG, JPEG, PNG, WEBP, MP4, WEBM, DOCX или PDF.',
                        'maxSizeMessage' => 'Файл слишком большой (максимум {{ limit }}).',
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PeopleArchive::class,
        ]);
    }
}