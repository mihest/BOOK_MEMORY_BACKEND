<?php

namespace App\EventSubscriber;

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

                if ($file->getMimeType() === 'application/msword') {
                    $newFilePath = $this->convertDocToDocx($file);
                    $this->updateFilePathInObject($object, $fileField, $nameFileField, $newFilePath);
                }
            }
        }
    }

    private function convertDocToDocx(File $file): string
    {
        $pathInfo = pathinfo($file->getRealPath());
        $outputDir = $pathInfo['dirname'];
        $inputPath = $file->getRealPath();
        $outputPath = $outputDir . '/' . $pathInfo['filename'] . '.docx';

        $command = sprintf('libreoffice --headless --convert-to docx --outdir %s %s', escapeshellarg($outputDir), escapeshellarg($inputPath));
        exec($command, $output, $resultCode);

        if ($resultCode !== 0 || !file_exists($outputPath)) {
            throw new \RuntimeException('Ошибка конвертации .doc в .docx');
        }

        return $outputPath;
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
}
