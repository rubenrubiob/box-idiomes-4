<?php

namespace App\Entity;

use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\ImageTrait;
use App\Entity\Traits\NameTrait;
use App\Entity\Traits\PositionTrait;
use App\Entity\Traits\SlugTrait;
use App\Enum\TeacherColorEnum;
use App\Repository\TeacherRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
#[ORM\Table]
#[Vich\Uploadable]
class Teacher extends AbstractBase implements \Stringable
{
    use DescriptionTrait;
    use ImageTrait;
    use NameTrait;
    use PositionTrait;
    use SlugTrait;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private string $slug;

    #[Assert\File(maxSize: '10M', mimeTypes: ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'])]
    #[Assert\Image(minWidth: 600, allowLandscape: false, allowPortrait: true)]
    #[Vich\UploadableField(mapping: 'teacher', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $color = 0;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $showInHomepage = true;

    public function getColor(): int
    {
        return $this->color;
    }

    public function getCssColor(): string
    {
        return 'c-'.TeacherColorEnum::getReversedEnumArray()[$this->getColor()];
    }

    public function setColor(int $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function isShowInHomepage(): bool
    {
        return $this->showInHomepage;
    }

    public function setShowInHomepage(bool $showInHomepage): self
    {
        $this->showInHomepage = $showInHomepage;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getName() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
