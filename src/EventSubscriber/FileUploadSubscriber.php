<?php

namespace App\EventSubscriber;

use Imagine\Imagick\Imagine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Event\Event;

class FileUploadSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'vich_uploader.post_upload' => 'onPostUpload',
        ];
    }

    public function onPostUpload(Event $event): void
    {
        $object = $event->getObject();
        $mapping = $event->getMapping();
        $nameFileField = $mapping->getFileNamePropertyName();
        $fileField = $mapping->getFilePropertyName();
        $file = '';

        $getterMethod = 'get' . ucfirst($fileField);
        if (method_exists($object, $getterMethod)) {
            $file = $object->$getterMethod();
        }

        if ($file !== '')
        {
            if ($file instanceof File) {

                if (str_starts_with($file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/webp') {
                    $newFilePath = $this->convertImageToWebP($file);
                    $this->updateFilePathInObject($object, $fileField, $nameFileField, $newFilePath);
                }
            }
        }
    }

    private function updateFilePathInObject(object $object, string $fileField, string $nameFileField, string $newFilePath): void
    {
        $setterMethod = 'set' . ucfirst($fileField);
        $setterNameMethod = 'set' . ucfirst($nameFileField);

        if (method_exists($object, $setterMethod) && method_exists($object, $setterNameMethod)) {
            $newFile = new File($newFilePath);

            $object->$setterMethod($newFile);
            $object->$setterNameMethod(basename($newFilePath));
        }
    }

    private function convertImageToWebP(File $file): string
    {
        $imagine = new Imagine();
        $image = $imagine->open($file->getRealPath());

        $pathInfo = pathinfo($file->getRealPath());
        $newFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        $image->save($newFilePath, ['format' => 'webp']);

        if (file_exists($file->getRealPath())) {
            @unlink($file->getRealPath());
        }

        return $newFilePath;
    }
}
