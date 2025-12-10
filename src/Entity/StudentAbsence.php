<?php

namespace App\Entity;

use App\Entity\Traits\StudentTrait;
use App\Repository\StudentAbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: StudentAbsenceRepository::class)]
#[UniqueEntity(['student', 'day'])]
#[ORM\Table(name: 'student_absence')]
class StudentAbsence extends AbstractBase implements \Stringable
{
    use StudentTrait;

    #[ORM\ManyToOne(targetEntity: Student::class, fetch: 'EAGER')]
    private Student $student;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: false)]
    private \DateTimeInterface $day;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    private ?bool $hasBeenNotified = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $notificationDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    private ?bool $hasBeenAccepted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $acceptedDate;

    public function getDay(): \DateTimeInterface
    {
        return $this->day;
    }

    public function getDayString(): string
    {
        return self::convertDateAsString($this->getDay());
    }

    public function setDay(\DateTimeInterface $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getCalendarTitle(): string
    {
        return '[Alumne] '.$this->getStudent()->getName();
    }

    public function isHasBeenNotified(): ?bool
    {
        return $this->hasBeenNotified;
    }

    public function getHasBeenNotified(): ?bool
    {
        return $this->isHasBeenNotified();
    }

    public function hasBeenNotified(): ?bool
    {
        return $this->isHasBeenNotified();
    }

    public function setHasBeenNotified(?bool $hasBeenNotified): self
    {
        $this->hasBeenNotified = $hasBeenNotified;

        return $this;
    }

    public function getNotificationDate(): ?\DateTimeInterface
    {
        return $this->notificationDate;
    }

    public function getNotificationDateString(): string
    {
        return self::convertDateTimeAsString($this->getNotificationDate());
    }

    public function setNotificationDate(?\DateTimeInterface $notificationDate): self
    {
        $this->notificationDate = $notificationDate;

        return $this;
    }

    public function isHasBeenAccepted(): ?bool
    {
        return $this->hasBeenAccepted;
    }

    public function getHasBeenAccepted(): ?bool
    {
        return $this->isHasBeenAccepted();
    }

    public function hasBeenAccepted(): ?bool
    {
        return $this->isHasBeenAccepted();
    }

    public function setHasBeenAccepted(?bool $hasBeenAccepted): self
    {
        $this->hasBeenAccepted = $hasBeenAccepted;

        return $this;
    }

    public function getAcceptedDate(): ?\DateTimeInterface
    {
        return $this->acceptedDate;
    }

    public function setAcceptedDate(?\DateTimeInterface $acceptedDate): self
    {
        $this->acceptedDate = $acceptedDate;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getDayString().' · '.$this->getStudent() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
