<?php

namespace App\Repository;

use App\Entity\AbstractBase;
use App\Entity\Event;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Form\Model\FilterCalendarEventModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class EventRepository extends ServiceEntityRepository
{
    public const string ALIAS = 'e';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getFilteredByBeginAndEndQB(\DateTimeInterface $startDate, \DateTimeInterface $endDate): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.begin BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'));
    }

    public function getFilteredByBeginAndEndQ(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Query
    {
        return $this->getFilteredByBeginAndEndQB($startDate, $endDate)->getQuery();
    }

    public function getFilteredByBeginAndEnd(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->getFilteredByBeginAndEndQ($startDate, $endDate)->getResult();
    }

    public function getEnabledFilteredByBeginAndEndQB(\DateTimeInterface $startDate, \DateTimeInterface $endDate): QueryBuilder
    {
        return $this->getFilteredByBeginAndEndQB($startDate, $endDate)
            ->andWhere('e.enabled = :enabled')
            ->setParameter('enabled', true);
    }

    public function getEnabledFilteredByBeginAndEndQ(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Query
    {
        return $this->getEnabledFilteredByBeginAndEndQB($startDate, $endDate)->getQuery();
    }

    public function getEnabledFilteredByBeginAndEnd(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->getEnabledFilteredByBeginAndEndQ($startDate, $endDate)->getResult();
    }

    public function getEnabledFilteredByBeginEndAndFilterCalendarEventFormQB(\DateTimeInterface $startDate, \DateTimeInterface $endDate, FilterCalendarEventModel $filter): QueryBuilder
    {
        $qb = $this->getEnabledFilteredByBeginAndEndQB($startDate, $endDate);
        $qb->leftJoin('e.group', 'g');
        if ($filter->getClassroom()) {
            $qb
                ->andWhere('e.classroom = :classroom')
                ->setParameter('classroom', $filter->getClassroom())
            ;
        }
        if ($filter->getTeacher()) {
            $qb
                ->andWhere('e.teacher = :teacher')
                ->setParameter('teacher', $filter->getTeacher())
            ;
        }
        if ($filter->getGroup()) {
            $qb
                ->andWhere('e.group = :group')
                ->setParameter('group', $filter->getGroup())
            ;
        }
        if ($filter->getTrainingCenter()) {
            $qb
                ->andWhere('g.trainingCenter = :center')
                ->setParameter('center', $filter->getTrainingCenter())
            ;
        }

        return $qb;
    }

    public function getEnabledFilteredByBeginEndAndFilterCalendarEventFormQ(\DateTimeInterface $startDate, \DateTimeInterface $endDate, FilterCalendarEventModel $filter): Query
    {
        return $this->getEnabledFilteredByBeginEndAndFilterCalendarEventFormQB($startDate, $endDate, $filter)->getQuery();
    }

    public function getEnabledFilteredByBeginEndAndFilterCalendarEventForm(\DateTimeInterface $startDate, \DateTimeInterface $endDate, FilterCalendarEventModel $filter): array
    {
        return $this->getEnabledFilteredByBeginEndAndFilterCalendarEventFormQ($startDate, $endDate, $filter)->getResult();
    }

    public function getEnabledFilteredByTeacherBeginAndEndQB(?Teacher $teacher, \DateTimeInterface $startDate, \DateTimeInterface $endDate): QueryBuilder
    {
        return $this->getEnabledFilteredByBeginAndEndQB($startDate, $endDate)
            ->andWhere('e.teacher = :teacher')
            ->setParameter('teacher', $teacher);
    }

    public function getEnabledFilteredByTeacherBeginAndEndQ(?Teacher $teacher, \DateTimeInterface $startDate, \DateTimeInterface $endDate): Query
    {
        return $this->getEnabledFilteredByTeacherBeginAndEndQB($teacher, $startDate, $endDate)->getQuery();
    }

    public function getEnabledFilteredByTeacherBeginAndEnd(?Teacher $teacher, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->getEnabledFilteredByTeacherBeginAndEndQ($teacher, $startDate, $endDate)->getResult();
    }

    public function getEnabledFilteredByBeginEndAndStudentQB(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Student $student): QueryBuilder
    {
        return $this->getEnabledFilteredByBeginAndEndQB($startDate, $endDate)
            ->join('e.students', 's')
            ->andWhere('s.id = :sid')
            ->setParameter('sid', $student->getId())
        ;
    }

    public function getEnabledFilteredByBeginEndAndStudentQ(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Student $student): Query
    {
        return $this->getEnabledFilteredByBeginEndAndStudentQB($startDate, $endDate, $student)->getQuery();
    }

    public function getEnabledFilteredByBeginEndAndStudent(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Student $student): array
    {
        return $this->getEnabledFilteredByBeginEndAndStudentQ($startDate, $endDate, $student)->getResult();
    }

    public function getPrivateLessonsByStudentYearAndMonthQB(Student $student, $year, $month): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->join('e.students', 's')
            ->join('e.group', 'cg')
            ->where('YEAR(e.begin) = :year')
            ->andWhere('MONTH(e.begin) = :month')
            ->andWhere('s.id = :sid')
            ->andWhere('cg.isForPrivateLessons = :isForPrivateLessons')
            ->andWhere('e.enabled = :enabled')
            ->setParameter('sid', $student->getId())
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('isForPrivateLessons', true)
            ->setParameter('enabled', true);
    }

    public function getPrivateLessonsByStudentYearAndMonthQ(Student $student, $year, $month): Query
    {
        return $this->getPrivateLessonsByStudentYearAndMonthQB($student, $year, $month)->getQuery();
    }

    public function getPrivateLessonsByStudentYearAndMonth(Student $student, $year, $month): array
    {
        return $this->getPrivateLessonsByStudentYearAndMonthQ($student, $year, $month)->getResult();
    }

    public function getPrivateLessonsAmountByStudentYearAndMonth(Student $student, $year, $month): int
    {
        return count($this->getPrivateLessonsByStudentYearAndMonth($student, $year, $month));
    }

    public function getEnabledFilteredByDateQB(\DateTimeInterface $date): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('DATE(e.begin) = :searchedDate')
            ->andWhere('e.enabled = :enabled')
            ->setParameter('searchedDate', $date->format(AbstractBase::DATABASE_DATE_STRING_FORMAT))
            ->setParameter('enabled', true);
    }

    public function getEnabledFilteredByDateQ(\DateTimeInterface $date): Query
    {
        return $this->getEnabledFilteredByDateQB($date)->getQuery();
    }

    public function getEnabledFilteredByDate(\DateTimeInterface $date): array
    {
        return $this->getEnabledFilteredByDateQ($date)->getResult();
    }

    public function getEnabledFilteredByDateSortedByBeginAndClassroomQB(\DateTimeInterface $date): QueryBuilder
    {
        return $this->getEnabledFilteredByDateQB($date)
            ->join('e.group', 'g')
            ->orderBy('e.begin')
            ->addOrderBy('e.end')
            ->addOrderBy('e.classroom')
            ->addOrderBy('g.name');
    }

    public function getEnabledFilteredByDateSortedByBeginAndClassroomQ(\DateTimeInterface $date): Query
    {
        return $this->getEnabledFilteredByDateSortedByBeginAndClassroomQB($date)->getQuery();
    }

    public function getEnabledFilteredByDateSortedByBeginAndClassroom(\DateTimeInterface $date): array
    {
        return $this->getEnabledFilteredByDateSortedByBeginAndClassroomQ($date)->getResult();
    }
}
