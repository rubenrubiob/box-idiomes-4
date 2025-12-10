<?php

namespace App\Admin;

use App\Doctrine\Enum\SortOrderTypeEnum;
use App\Entity\AbstractBase;
use App\Entity\ClassGroup;
use App\Entity\Event;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Enum\EventClassroomTypeEnum;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class EventAdmin extends AbstractBaseAdmin
{
    protected $classnameLabel = 'Event';

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::PER_PAGE] = 400;
        $sortValues[DatagridInterface::SORT_ORDER] = SortOrderTypeEnum::DESC;
        $sortValues[DatagridInterface::SORT_BY] = 'begin';
    }

    public function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'classrooms/timetable';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        parent::configureRoutes($collection);
        $collection
            ->add('batchedit', $this->getRouterIdParameter().'/batch-edit')
            ->add('batchdelete', $this->getRouterIdParameter().'/batch-delete')
            ->add('apiget', $this->getRouterIdParameter().'/api/get')
            ->add('apiattendedclass', $this->getRouterIdParameter().'/api/{student}/attended-the-class')
            ->add('apinotattendedclass', $this->getRouterIdParameter().'/api/{student}/not-attended-the-class')
            ->remove('delete')
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('backend.admin.dates', $this->getFormMdSuccessBoxArray('backend.admin.dates', 3))
            ->add(
                'begin',
                DateTimePickerType::class,
                [
                    'label' => 'backend.admin.event.begin',
                    'format' => 'd/M/y H:mm',
                    'required' => true,
                ]
            )
            ->add(
                'end',
                DateTimePickerType::class,
                [
                    'label' => 'backend.admin.event.end',
                    'format' => 'd/M/y H:mm',
                    'required' => true,
                ]
            );
        if ($this->isFormToCreateNewRecord()) {
            $form
                ->add(
                    'dayFrequencyRepeat',
                    null,
                    [
                        'label' => 'backend.admin.event.dayFrequencyRepeat',
                        'required' => false,
                        'help' => 'backend.admin.event.dayFrequencyRepeat_help',
                    ]
                )
                ->add(
                    'until',
                    DateTimePickerType::class,
                    [
                        'label' => 'backend.admin.event.until',
                        'format' => 'd/M/y H:mm',
                        'required' => false,
                    ]
                );
        }
        $form
            ->end()
            ->with('backend.admin.general', $this->getFormMdSuccessBoxArray('backend.admin.general', 3))
            ->add(
                'classroom',
                ChoiceType::class,
                [
                    'label' => 'backend.admin.event.classroom',
                    'choices' => EventClassroomTypeEnum::getEnumArray(),
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                ]
            )
            ->add(
                'teacher',
                EntityType::class,
                [
                    'label' => 'backend.admin.event.teacher',
                    'required' => true,
                    'class' => Teacher::class,
                    'choice_label' => 'name',
                    'query_builder' => $this->em->getRepository(Teacher::class)->getEnabledSortedByNameQB(),
                ]
            )
            ->add(
                'group',
                EntityType::class,
                [
                    'label' => 'backend.admin.event.group',
                    'required' => true,
                    'class' => ClassGroup::class,
                    'query_builder' => $this->em->getRepository(ClassGroup::class)->getEnabledSortedByCodeQB(),
                ]
            )
            ->end()
            ->with('backend.admin.event.students', $this->getFormMdSuccessBoxArray('backend.admin.event.students'))
            ->add(
                'students',
                EntityType::class,
                [
                    'label' => 'backend.admin.event.students',
                    'required' => false,
                    'multiple' => true,
                    'class' => Student::class,
                    'query_builder' => $this->em->getRepository(Student::class)->getAllSortedBySurnameQB(),
                    'choice_label' => function (Student $student) {
                        return $student->getFullCanonicalName().($student->isEnabled() ? '' : ' (*** BAIXA ***)');
                    },
                ]
            )
            ->end()
            ->with('backend.admin.assistance', $this->getFormMdSuccessBoxArray('backend.admin.assistance'))
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add(
                'begin',
                DateTimeFilter::class,
                [
                    'label' => 'backend.admin.event.begin',
                    'field_type' => DateTimePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy HH:mm',
                    ],
                ]
            )
            ->add(
                'end',
                DateTimeFilter::class,
                [
                    'label' => 'backend.admin.event.end',
                    'field_type' => DateTimePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy HH:mm',
                    ],
                ]
            )
            ->add(
                'classroom',
                null,
                [
                    'label' => 'backend.admin.event.classroom',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'expanded' => false,
                        'multiple' => false,
                        'choices' => EventClassroomTypeEnum::getEnumArray(),
                    ],
                ]
            )
            ->add(
                'teacher',
                null,
                [
                    'label' => 'backend.admin.event.teacher',
                ]
            )
            ->add(
                'group',
                null,
                [
                    'label' => 'backend.admin.event.group',
                ]
            )
            ->add(
                'students',
                null,
                [
                    'label' => 'backend.admin.event.students',
                ]
            )
        ;
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = current($query->getRootAliases());
        $query
            ->leftJoin($rootAlias.'.students', 's')
            ->andWhere($rootAlias.'.enabled = :enabled')
            ->setParameter('enabled', true)
            ->addSelect('COUNT(s.id) AS studentsCount')
            ->groupBy($rootAlias.'.id')
        ;

        return $query;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add(
                'begin',
                FieldDescriptionInterface::TYPE_DATETIME,
                [
                    'label' => 'backend.admin.event.begin',
                    'format' => AbstractBase::DATETIME_STRING_FORMAT,
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'end',
                FieldDescriptionInterface::TYPE_DATETIME,
                [
                    'label' => 'backend.admin.event.end',
                    'format' => AbstractBase::DATETIME_STRING_FORMAT,
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'classroom',
                null,
                [
                    'label' => 'backend.admin.event.classroom',
                    'template' => 'Admin/Cells/list__cell_classroom_type.html.twig',
                ]
            )
            ->add(
                'teacher',
                null,
                [
                    'label' => 'backend.admin.event.teacher',
                    'editable' => false,
                    'associated_property' => 'name',
                    'sortable' => true,
                    'sort_field_mapping' => ['fieldName' => 'name'],
                    'sort_parent_association_mappings' => [['fieldName' => 'teacher']],
                ]
            )
            ->add(
                'group',
                null,
                [
                    'label' => 'backend.admin.event.group',
                    'editable' => true,
                    'sortable' => true,
                    'sort_field_mapping' => ['fieldName' => 'code'],
                    'sort_parent_association_mappings' => [['fieldName' => 'group']],
                ]
            )
            ->add(
                'studentsCount',
                null,
                [
                    'label' => 'backend.admin.event.students',
                    'virtual_field' => true,
                    'template' => 'Admin/Cells/list__cell_classroom_students_amount.html.twig',
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                ListMapper::NAME_ACTIONS,
                null,
                [
                    'label' => 'backend.admin.actions',
                    'header_style' => 'width:116px',
                    'header_class' => 'text-right',
                    'row_align' => 'right',
                    'actions' => [
                        'edit' => ['template' => 'Admin/Buttons/list__action_edit_button.html.twig'],
                        'batchedit' => ['template' => 'Admin/Buttons/list__action_event_batch_edit_button.html.twig'],
                        'batchdelete' => ['template' => 'Admin/Buttons/list__action_batch_delete_button.html.twig'],
                    ],
                ]
            );
    }

    public function configureExportFields(): array
    {
        return [
            'beginString',
            'endString',
            'classroomString',
            'teacher',
            'group',
            'studentsAmount',
            'studentsString',
        ];
    }

    /**
     * Create event and all of his sibilings if there is a repeat frequency.
     *
     * @param Event $object
     *
     * @throws \Exception
     */
    public function postPersist($object): void
    {
        if ($object->getDayFrequencyRepeat() && $object->getUntil()) {
            $currentBegin = $object->getBegin();
            $currentEnd = $object->getEnd();
            $currentBegin->add(new \DateInterval('P'.$object->getDayFrequencyRepeat().'D'));
            $currentEnd->add(new \DateInterval('P'.$object->getDayFrequencyRepeat().'D'));
            $previousEvent = $object;
            $found = false;

            while ($currentBegin->format('Y-m-d H:i') <= $object->getUntil()->format('Y-m-d H:i')) {
                $event = new Event();
                $event
                    ->setBegin($currentBegin)
                    ->setEnd($currentEnd)
                    ->setTeacher($previousEvent->getTeacher())
                    ->setClassroom($previousEvent->getClassroom())
                    ->setGroup($previousEvent->getGroup())
                    ->setStudents($previousEvent->getStudents())
                    ->setPrevious($previousEvent)
                ;

                $this->em->persist($event);
                $this->em->flush();

                $currentBegin->add(new \DateInterval('P'.$object->getDayFrequencyRepeat().'D'));
                $currentEnd->add(new \DateInterval('P'.$object->getDayFrequencyRepeat().'D'));
                $previousEvent = $event;
                $found = true;
            }

            if ($found) {
                $previousEvent = $event->getPrevious();
                while (!is_null($previousEvent)) {
                    $previousEvent->setNext($event);
                    $this->em->flush();
                    $event = $previousEvent;
                    $previousEvent = $previousEvent->getPrevious();
                }
            }
        }
    }
}
