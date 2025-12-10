<?php

namespace App\Entity;

use App\Entity\Traits\StudentTrait;
use App\Enum\StudentEvaluationEnum;
use App\Repository\StudentEvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: StudentEvaluationRepository::class)]
#[UniqueEntity(['student', 'course', 'evaluation'])]
#[ORM\Table(
    name: 'student_evaluation',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'student_course_evaluation_key',
            columns: ['student_id', 'course_id', 'evaluation_id']
        ),
    ]
)]
class StudentEvaluation extends AbstractBase implements \Stringable
{
    use StudentTrait;

    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Student::class, fetch: 'EAGER')]
    private Student $student;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    private ?bool $hasBeenNotified = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $notificationDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    private ?bool $hasBeenAccepted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $acceptedDate;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    private ?bool $hasBeenClosed = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closedDate;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $course;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => StudentEvaluationEnum::FIRST_TRIMESTER])]
    private int $evaluation = StudentEvaluationEnum::FIRST_TRIMESTER;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $writting = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $reading = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $useOfEnglish = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $listening = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $speaking = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $behaviour = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $globalMark = null;

    public function __construct()
    {
        $this->course = (int) new \DateTimeImmutable()->format('Y');
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

    public function getHasBeenClosed(): ?bool
    {
        return $this->hasBeenClosed;
    }

    public function setHasBeenClosed(?bool $hasBeenClosed): self
    {
        $this->hasBeenClosed = $hasBeenClosed;

        return $this;
    }

    public function getClosedDate(): ?\DateTimeInterface
    {
        return $this->closedDate;
    }

    public function setClosedDate(?\DateTimeInterface $closedDate): self
    {
        $this->closedDate = $closedDate;

        return $this;
    }

    public function getCourse(): int
    {
        return $this->course;
    }

    public function getFullCourseAsString(): string
    {
        return sprintf('%d / %d', $this->getCourse(), $this->getCourse() + 1);
    }

    public function setCourse(int $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getEvaluation(): int
    {
        return $this->evaluation;
    }

    public function setEvaluation(int $evaluation): self
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function getWritting(): ?string
    {
        return $this->writting;
    }

    public function setWritting(?string $writting): self
    {
        $this->writting = $writting;

        return $this;
    }

    public function getReading(): ?string
    {
        return $this->reading;
    }

    public function setReading(?string $reading): self
    {
        $this->reading = $reading;

        return $this;
    }

    public function getUseOfEnglish(): ?string
    {
        return $this->useOfEnglish;
    }

    public function setUseOfEnglish(?string $useOfEnglish): self
    {
        $this->useOfEnglish = $useOfEnglish;

        return $this;
    }

    public function getListening(): ?string
    {
        return $this->listening;
    }

    public function setListening(?string $listening): self
    {
        $this->listening = $listening;

        return $this;
    }

    public function getSpeaking(): ?string
    {
        return $this->speaking;
    }

    public function setSpeaking(?string $speaking): self
    {
        $this->speaking = $speaking;

        return $this;
    }

    public function getBehaviour(): ?string
    {
        return $this->behaviour;
    }

    public function setBehaviour(?string $behaviour): self
    {
        $this->behaviour = $behaviour;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getGlobalMark(): ?string
    {
        return $this->globalMark;
    }

    public function setGlobalMark(?string $globalMark): self
    {
        $this->globalMark = $globalMark;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ? $this->getCourse().' · '.$this->getEvaluation().' · '.$this->getStudent() : AbstractBase::DEFAULT_NULL_STRING;
    }
}
