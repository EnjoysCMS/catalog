<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_product_files')]
class ProductFiles
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'file_path', type: 'string')]
    private string $filePath;

    #[ORM\Column(name: 'original_filename', type: 'string')]
    private string $originalFilename;

    #[ORM\Column(name: 'file_extension', type: 'string')]
    private string $fileExtension;

    #[ORM\Column(name: 'file_size', type: 'integer')]
    private int $fileSize;


    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $status = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $downloads = 0;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'files')]
    private Product $product;


    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'file_default'])]
    private string $storage;

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDownloads(): int
    {
        return $this->downloads;
    }

    public function setDownloads(int $downloads): void
    {
        $this->downloads = $downloads;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = (empty($title)) ? null : $title;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = (empty($description)) ? null : $description;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    public function getFilename(): string
    {
        return str_replace(
            ' ',
            '_',
            ($this->getTitle() ?? str_replace(
                    '.' . $this->getFileExtension(),
                    '',
                    $this->getOriginalFilename()
                )) . '.' . $this->getFileExtension()
        );
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function setStorage(string $storage): void
    {
        $this->storage = $storage;
    }
}
