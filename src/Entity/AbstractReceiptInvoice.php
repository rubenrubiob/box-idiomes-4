<?php

namespace App\Entity;

use App\Entity\Traits\MonthTrait;
use App\Entity\Traits\TrainingCenterTrait;
use App\Entity\Traits\YearTrait;
use App\Enum\StudentPaymentEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class AbstractReceiptInvoice extends AbstractBase
{
    use MonthTrait;
    use TrainingCenterTrait;
    use YearTrait;

    #[ORM\ManyToOne(targetEntity: Student::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id')]
    protected ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id')]
    protected ?Person $person = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $isPayed = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $isSended = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $sendDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $isSepaXmlGenerated = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $sepaXmlGeneratedDate = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    protected ?float $baseAmount = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $discountApplied = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $isForPrivateLessons = false;

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): self
    {
        $this->student = $student;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getDateString(): string
    {
        return self::convertDateAsString($this->getDate());
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function isPayed(): ?bool
    {
        return $this->isPayed;
    }

    public function getIsPayed(): ?bool
    {
        return $this->isPayed();
    }

    public function getIsPayedString(): string
    {
        return self::convertBooleanValueAsString($this->isPayed());
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

    public function isSended(): ?bool
    {
        return $this->isSended;
    }

    public function getIsSended(): ?bool
    {
        return $this->isSended();
    }

    public function getIsSendedString(): string
    {
        return self::convertBooleanValueAsString($this->isSended());
    }

    public function setIsSended(?bool $isSended): self
    {
        $this->isSended = $isSended;

        return $this;
    }

    public function getSendDate(): ?\DateTimeInterface
    {
        return $this->sendDate;
    }

    public function getSendDateString(): string
    {
        return self::convertDateAsString($this->getSendDate());
    }

    public function setSendDate(?\DateTimeInterface $sendDate): self
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    public function isReadyToGenerateSepa(): bool
    {
        $result = true;
        /** @var Student|Person $subject */
        $subject = $this->getMainSubject();
        if (StudentPaymentEnum::BANK_ACCOUNT_NUMBER !== $subject->getPayment()) {
            $result = false;
        }
        if ($subject->getBank() && !$subject->getBank()->getAccountNumber()) {
            $result = false;
        }

        return $result;
    }

    public function isSepaXmlGenerated(): ?bool
    {
        return $this->isSepaXmlGenerated;
    }

    public function getSepaXmlGenerated(): ?bool
    {
        return $this->isSepaXmlGenerated();
    }

    public function getIsSepaXmlGeneratedString(): string
    {
        return self::convertBooleanValueAsString($this->isSepaXmlGenerated());
    }

    public function setIsSepaXmlGenerated(?bool $isSepaXmlGenerated): self
    {
        $this->isSepaXmlGenerated = $isSepaXmlGenerated;

        return $this;
    }

    public function getSepaXmlGeneratedDate(): ?\DateTimeInterface
    {
        return $this->sepaXmlGeneratedDate;
    }

    public function getSepaXmlGeneratedDateString(): string
    {
        return self::convertDateAsString($this->getSepaXmlGeneratedDate());
    }

    public function setSepaXmlGeneratedDate(?\DateTimeInterface $sepaXmlGeneratedDate): self
    {
        $this->sepaXmlGeneratedDate = $sepaXmlGeneratedDate;

        return $this;
    }

    public function getBaseAmount(): ?float
    {
        return $this->baseAmount;
    }

    public function getBaseAmountString(): string
    {
        return number_format($this->baseAmount, 2, ',', '.').'€';
    }

    public function setBaseAmount(?float $baseAmount): self
    {
        $this->baseAmount = $baseAmount;

        return $this;
    }

    public function isDiscountApplied(): ?bool
    {
        return $this->discountApplied;
    }

    public function getDiscountApplied(): ?bool
    {
        return $this->isDiscountApplied();
    }

    public function getDiscountAppliedString(): string
    {
        return self::convertBooleanValueAsString($this->isDiscountApplied());
    }

    public function setDiscountApplied(?bool $discountApplied): self
    {
        $this->discountApplied = $discountApplied;

        return $this;
    }

    public function isForPrivateLessons(): ?bool
    {
        return $this->isForPrivateLessons;
    }

    public function getIsForPrivateLessons(): ?bool
    {
        return $this->isForPrivateLessons();
    }

    public function getIsForPrivateLessonsString(): string
    {
        return self::convertBooleanValueAsString($this->isForPrivateLessons());
    }

    public function setIsForPrivateLessons(?bool $isForPrivateLessons): self
    {
        $this->isForPrivateLessons = $isForPrivateLessons;

        return $this;
    }

    /**
     * Get parent's email first, or student's one if it's not set.
     */
    public function getMainEmail(): ?string
    {
        return $this->getMainSubject()->getEmail();
    }

    /**
     * Get parent's name first, or student's one if it's not set.
     */
    public function getMainEmailName(): string
    {
        return $this->getMainSubject()->getFullName();
    }

    /**
     * Get parent's bank, or student's one if it's not set.
     */
    public function getMainBank(): ?Bank
    {
        return $this->getMainSubject()->getBank();
    }

    /**
     * Get parent's debtor mandates, or student's one if it's not set.
     */
    public function getDebtorMandate(): string
    {
        return $this->getMainSubject()->getDebtorMandate();
    }

    /**
     * Get parent's debtor mandate sign date, or student's one if it's not set.
     */
    public function getDebtorMandateSignDate(): string
    {
        return $this->getMainSubject()->getDebtorMandateSignDate();
    }

    /**
     * Get main subject, obtains who is most important actor: parent (first) or student.
     */
    public function getMainSubject(): Student|Person
    {
        /** @var Student|Person $subject */
        $subject = $this->getStudent();
        if (!is_null($subject->getParent())) {
            $subject = $subject->getParent();
        }

        return $subject;
    }
}
