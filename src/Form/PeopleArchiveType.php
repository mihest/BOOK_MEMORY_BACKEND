<?php

namespace App\Form;

use App\Entity\PeopleArchive;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PeopleArchiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'attr' => ['required' => true],
                'label_attr' => [
                    'class' => 'required',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Заголовок не может быть пустым.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Заголовок не должен превышать {{ limit }} символов.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
            ]);

        // Динамически добавляем поле mediaFile в зависимости от состояния записи
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            // Проверяем, существует ли запись (есть ли ID)
            $isEdit = $data instanceof PeopleArchive && $data->getId() !== null;

            $form->add('mediaFile', VichFileType::class, [
                'label' => 'Медиа',
                'attr' => $isEdit ? [] : [
                    'required' => true,
                    'accept' => '.jpg,.jpeg,.png,.webp,.mp4,.ogg,.docx,.pdf,.mp3',
                ],
                'label_attr' => $isEdit ? [] : ['class' => 'required'],
                'allow_delete' => false,
                'download_uri' => true,
                'help' => '
                    <div class="mt-2">
                        <strong>Допустимые форматы:</strong><br>
                        <span class="badge badge-info">*.jpg</span>
                        <span class="badge badge-info">*.jpeg</span>
                        <span class="badge badge-info">*.png</span>
                        <span class="badge badge-info">*.mp4</span>
                        <span class="badge badge-info">*.mp3</span>
                        <span class="badge badge-info">*.ogg</span>
                        <span class="badge badge-info">*.pdf</span>
                        <span class="badge badge-info">*.docx</span>
                    </div>
                ',
                'constraints' => [
                    new File([
                        'maxSize' => '20M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'video/mp4',
                            'audio/mpeg',
                            'audio/ogg',
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите файл в формате JPG, JPEG, PNG, MP4, MP3, OGG, PDF или DOCX.',
                        'maxSizeMessage' => 'Файл слишком большой (максимум {{ limit }}).',
                    ]),
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PeopleArchive::class,
        ]);
    }
}
