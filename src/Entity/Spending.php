<?php

namespace App\Entity;

use App\Entity\Traits\BaseAmountTrait;
use App\Entity\Traits\DocumentFileTrait;
use App\Enum\StudentPaymentEnum;
use App\Repository\SpendingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: SpendingRepository::class)]
#[Vich\Uploadable]
class Spending extends AbstractBase implements \Stringable
{
    use BaseAmountTrait;
    use DocumentFileTrait;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: false)]
    private \DateTimeInterface $date;

    #[ORM\ManyToOne(targetEntity: SpendingCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private ?SpendingCategory $category = null;

    #[ORM\ManyToOne(targetEntity: Provider::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id')]
    private ?Provider $provider = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isPayed = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $paymentMethod = StudentPaymentEnum::BANK_ACCOUNT_NUMBER;

    #[Assert\File(maxSize: '10M', mimeTypes: ['application/pdf', 'application/x-pdf'])]
    #[Vich\UploadableField(mapping: 'spending', fileNameProperty: 'document')]
    private ?File $documentFile = null;

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getDateString(): string
    {
        return self::convertDateAsString($this->getDate());
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCategory(): ?SpendingCategory
    {
        return $this->category;
    }

    public function setCategory(?SpendingCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isPayed(): ?bool
    {
        return $this->isPayed;
    }

    public function getPayed(): ?bool
    {
        return $this->isPayed();
    }

    public function setIsPayed(?bool $isPayed): self
    {
        $this->isPayed = $isPayed;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function getPaymentDateString(): string
    {
        return self::convertDateAsString($this->getPaymentDate());
    }

    public function setPaymentDate(?\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getPaymentMethod(): int
    {
        return $this->paymentMethod;
    }

    public function getPaymentMethodString(): string
    {
        return array_key_exists($this->getPaymentMethod(), StudentPaymentEnum::getEnumTranslatedArray()) ? StudentPaymentEnum::getEnumTranslatedArray()[$this->getPaymentMethod()] : self::DEFAULT_NULL_STRING;
    }

    public function setPaymentMethod(int $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getDateString().' · '.$this->getProvider().' · '.$this->getBaseAmountString() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
