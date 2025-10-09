<?php 
namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class FileUploader
{
    private Imagine $imagine;
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {
        $this->imagine = new Imagine();
    }

    public function upload(UploadedFile $file,?string $directoryParameter = null): string
    {
        $directoryParameter = ($directoryParameter == null) ? $this->getTargetDirectory() : $directoryParameter;

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($directoryParameter, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $fileName;
    }



    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function uploadImage(UploadedFile $file, ?string $directoryParameter = null): string
    {
        $directoryParameter = $directoryParameter ?? $this->getTargetDirectory();

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($directoryParameter, $fileName);
            $this->resizeImage($directoryParameter . '/' . $fileName);
        } catch (FileException $e) {
            // Handle exception if something happens during file upload
            throw new \RuntimeException(sprintf('Could not move the file "%s" to the directory "%s".', $fileName, $directoryParameter));
        }

        return $fileName;
    }

    private function resizeImage(string $filePath): void
    {
        $image = $this->imagine->open($filePath);
        $size = new Box(80, 80); // Set your desired width and height

        $image->resize($size)
              ->save($filePath, ['jpeg_quality' => 80, 'png_compression_level' => 9]);
    }
}

?>