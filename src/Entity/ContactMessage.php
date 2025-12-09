<?php

namespace App\Entity;

use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\DocumentFileTrait;
use App\Entity\Traits\NameTrait;
use App\Repository\ContactMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Table]
#[Vich\Uploadable]
class ContactMessage extends AbstractBase implements \Stringable
{
    use DescriptionTrait;
    use DocumentFileTrait;
    use NameTrait;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $checked = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $answered = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, length: 4000)]
    private ?string $message = null;

    #[Assert\File(maxSize: '10M')]
    #[Vich\UploadableField(mapping: 'contact_message', fileNameProperty: 'document')]
    private ?File $documentFile = null;

    private bool $privacy;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): string
    {
        $this->email = $email;

        return $this;
    }

    public function getChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(?bool $checked): self
    {
        $this->checked = $checked;

        return $this;
    }

    public function getAnswered(): ?bool
    {
        return $this->answered;
    }

    public function setAnswered(?bool $answered): self
    {
        $this->answered = $answered;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function isPrivacy(): bool
    {
        return $this->privacy;
    }

    public function setPrivacy(bool $privacy): self
    {
        $this->privacy = $privacy;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getCreatedAtString().' · '.$this->getEmail() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
