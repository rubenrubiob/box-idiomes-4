<?php

namespace App\Entity;

use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\ImageTrait;
use App\Entity\Traits\PositionTrait;
use App\Entity\Traits\SlugTrait;
use App\Repository\ServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table]
#[Vich\Uploadable]
class Service extends AbstractBase implements \Stringable
{
    use DescriptionTrait;
    use ImageTrait;
    use PositionTrait;
    use SlugTrait;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private string $slug;

    #[Assert\File(maxSize: '10M', mimeTypes: ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'])]
    #[Assert\Image(minWidth: 1200)]
    #[Vich\UploadableField(mapping: 'service', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getTitle() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
