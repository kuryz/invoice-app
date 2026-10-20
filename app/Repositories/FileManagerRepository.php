<?php
namespace App\Repositories;

class FileManagerRepository
{
    protected array $files = [
        'users' => [
            'path' => 'assets/images/users/profile',
            'size' => 2048,
            'extensions' => ['jpg', 'jpeg', 'png'],
        ],

        'blog' => [
            'path' => 'assets/images/blog/',
            'size' => 5120,
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        ],

        'documents' => [
            'path' => 'assets/documents/',
            'size' => 10240,
            'extensions' => ['pdf', 'doc', 'docx'],
        ],
    ];

    protected $file;
    public $path;
    public $size;
    public $old;
    public $thumb;
    public $url;
    public $sizeInBytes;
    public $extensions;
    public $filename;


    public function __construct($file = null)
    {
        $this->file = $file;
    }


    public function __call($name, $arguments)
    {
        if (!isset($this->files[$name])) {
            throw new \InvalidArgumentException("File manager key '{$name}' does not exist.");
        }

        return (object) $this->files[$name];
    }


    public function upload()
    {
        if (!$this->file) {
            throw new \Exception('No file selected.');
        }

        $this->validateFile();

        $uploadPath = $this->getUploadPath();

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $extension = strtolower($this->file->getClientOriginalExtension());

        $this->filename = time() . '_' . uniqid() . '.' . $extension;

        $this->file->move($uploadPath, $this->filename);


        if ($this->old) {
            $this->deleteOldFile();
        }


        if ($this->thumb) {
            $this->createThumbnail($uploadPath . '/' . $this->filename);
        }

        return $this->filename;
    }


    protected function validateFile()
    {
        if (method_exists($this->file, 'isValid') && !$this->file->isValid()) {
            throw new \Exception('Invalid file upload.');
        }


        if ($this->size && $this->file->getSize() > ($this->size * 1024)) {
            throw new \Exception('File size exceeds allowed limit.');
        }


        if (!empty($this->extensions)) {
            $extension = strtolower($this->file->getClientOriginalExtension());

            if (!in_array($extension, $this->extensions)) {
                throw new \Exception('File extension not allowed.');
            }
        }
    }


    protected function getUploadPath()
    {
        return BASE_PATH . '/public/' . $this->path;
    }


    protected function deleteOldFile()
    {
        $oldFile = $this->getUploadPath() . '/' . $this->old;

        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }


    protected function createThumbnail($file)
    {
        // Add thumbnail generation logic here
    }
}