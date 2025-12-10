<?php

namespace App\Admin;

use App\Doctrine\Enum\SortOrderTypeEnum;
use App\Entity\BankCreditorSepa;
use App\Entity\City;
use App\Entity\ClassGroup;
use App\Entity\Invoice;
use App\Entity\Person;
use App\Entity\Receipt;
use App\Entity\Student;
use App\Entity\Tariff;
use App\Entity\TrainingCenter;
use App\Enum\SchoolYearChoicesGeneratorEnum;
use App\Enum\StudentAgesEnum;
use App\Enum\StudentPaymentEnum;
use App\Model\BeginEndSchoolYearMoment;
use App\Repository\BankRepository;
use App\Repository\EventRepository;
use App\Repository\PersonRepository;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Exception\ModelManagerThrowable;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class StudentAdmin extends AbstractBaseAdmin
{
    protected $classnameLabel = 'Student';

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = SortOrderTypeEnum::ASC;
        $sortValues[DatagridInterface::SORT_BY] = 'surname';
    }

    public function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'students/student';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->add('imagerights', $this->getRouterIdParameter().'/image-rights')
            ->add('sepaagreement', $this->getRouterIdParameter().'/sepa-agreement')
            ->add('mailing', 'mailing')
            ->add('mailing_reset', 'mailing-reset')
            ->add('write_mailing', 'mailing-write')
            ->add('deliver_massive_mailing', 'mailing-delivery')
        ;
    }

    public function configureBatchActions(array $actions): array
    {
        unset($actions['delete']);
        if ($this->hasRoute('edit') && $this->hasAccess('edit')) {
            $actions['markasinactive'] = [
                'label' => 'backend.admin.student.mark_as_inactive_batch_action',
                'translation_domain' => 'messages',
                'ask_confirmation' => false,
            ];
        }

        return $actions;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('backend.admin.general', $this->getFormMdSuccessBoxArray('backend.admin.general', 3))
            ->add(
                'name',
                null,
                [
                    'label' => 'backend.admin.student.name',
                ]
            )
            ->add(
                'surname',
                null,
                [
                    'label' => 'backend.admin.student.surname',
                ]
            )
            ->add(
                'parent',
                EntityType::class,
                [
                    'label' => 'backend.admin.student.parent',
                    'required' => false,
                    'class' => Person::class,
                    'choice_label' => 'fullcanonicalname',
                    'query_builder' => $this->em->getRepository(Person::class)->getEnabledSortedBySurnameQB(),
                ]
            )
            ->add(
                'comments',
                TextareaType::class,
                [
                    'label' => 'backend.admin.student.comments',
                    'required' => false,
                    'attr' => [
                        'rows' => 8,
                        'style' => 'resize:vertical',
                    ],
                ]
            )
            ->end()
            ->with('backend.admin.contact.contact', $this->getFormMdSuccessBoxArray('backend.admin.contact.contact', 3))
        ;
        if ($this->isAdminUser()) {
            $form
                ->add(
                    'phone',
                    null,
                    [
                        'label' => 'backend.admin.student.phone',
                        'required' => false,
                    ]
                )
                ->add(
                    'email',
                    null,
                    [
                        'label' => 'backend.admin.student.email',
                        'required' => false,
                    ]
                )
            ;
        }
        $form
            ->add(
                'address',
                null,
                [
                    'label' => 'backend.admin.student.address',
                    'required' => false,
                ]
            )
            ->add(
                'city',
                EntityType::class,
                [
                    'label' => 'backend.admin.student.city',
                    'required' => true,
                    'class' => City::class,
                    'choice_label' => 'name',
                    'query_builder' => $this->em->getRepository(City::class)->getEnabledSortedByNameQB(),
                ]
            )
            ->end()
            ->with('backend.admin.student.payment_information', $this->getFormMdSuccessBoxArray('backend.admin.student.payment_information', 3))
            ->add(
                'payment',
                ChoiceType::class,
                [
                    'label' => 'backend.admin.student.payment',
                    'choices' => StudentPaymentEnum::getEnumArray(),
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'help' => 'backend.admin.student.payment_no_parent_help',
                ]
            )
            ->add(
                'bank',
                AdminType::class,
                [
                    'label' => ' ',
                    'required' => false,
                    'btn_add' => false,
                    'by_reference' => false,
                ]
            )
            ->add(
                'bankCreditorSepa',
                EntityType::class,
                [
                    'label' => 'backend.admin.bank.creditor_bank_name',
                    'help' => 'backend.admin.bank.creditor_bank_name_help',
                    'required' => true,
                    'class' => BankCreditorSepa::class,
                    'query_builder' => $this->em->getRepository(BankCreditorSepa::class)->getEnabledSortedByNameQB(),
                ]
            )
            ->end()
            ->with('backend.admin.controls', $this->getFormMdSuccessBoxArray('backend.admin.controls', 3))
            ->add(
                'dni',
                null,
                [
                    'label' => 'backend.admin.student.dni',
                    'required' => false,
                ]
            )
            ->add(
                'birthDate',
                DatePickerType::class,
                [
                    'label' => 'backend.admin.student.birthDate',
                    'format' => 'd/M/y',
                    'datepicker_options' => [
                        'localization' => [
                            'locale' => 'es',
                        ],
                    ],
                ]
            )
            ->add(
                'dischargeDate',
                DatePickerType::class,
                [
                    'label' => 'backend.admin.student.dischargeDate',
                    'format' => 'd/M/y',
                    'required' => false,
                ]
            )
            ->add(
                'unsubscriptionDate',
                DatePickerType::class,
                [
                    'label' => 'backend.admin.student.unsubscriptionDate',
                    'format' => 'd/M/y',
                    'required' => false,
                ]
            )
            ->add(
                'schedule',
                null,
                [
                    'label' => 'backend.admin.student.schedule',
                ]
            )
            ->add(
                'tariff',
                EntityType::class,
                [
                    'label' => 'backend.admin.student.tariff',
                    'required' => true,
                    'class' => Tariff::class,
                    'query_builder' => $this->em->getRepository(Tariff::class)->findAllSortedByYearAndPriceQB(),
                    'help' => 'backend.admin.student.tariff_helper',
                ]
            )
            ->add(
                'trainingCenter',
                EntityType::class,
                [
                    'label' => 'backend.admin.class_group.training_center',
                    'help' => 'backend.admin.student.training_center_help',
                    'required' => true,
                    'class' => TrainingCenter::class,
                    'query_builder' => $this->em->getRepository(TrainingCenter::class)->getEnabledSortedByNameQB(),
                ]
            )
            ->add(
                'hasImageRightsAccepted',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.imagerigths.checkbox_label',
                    'required' => false,
                ]
            )
            ->add(
                'hasSepaAgreementAccepted',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.sepaagreement.checkbox_label',
                    'required' => false,
                ]
            )
            ->add(
                'isPaymentExempt',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.student.is_payment_excempt',
                    'required' => false,
                ]
            )
            ->add(
                'hasAcceptedInternalRegulations',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.internalregulations.checkbox_label',
                    'required' => false,
                ]
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.enabled',
                    'required' => false,
                ]
            )
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add(
                'dni',
                null,
                [
                    'label' => 'backend.admin.student.dni',
                ]
            )
            ->add(
                'name',
                null,
                [
                    'label' => 'backend.admin.student.name',
                ]
            )
            ->add(
                'surname',
                null,
                [
                    'label' => 'backend.admin.student.surname',
                ]
            )
            ->add(
                'parent',
                ModelFilter::class,
                [
                    'label' => 'backend.admin.student.parent',
                    'field_type' => ModelAutocompleteType::class,
                    'field_options' => [
                        'class' => Person::class,
                        'property' => ['name', 'surname'],
                    ],
                ]
            )
            ->add(
                'comments',
                null,
                [
                    'label' => 'backend.admin.student.comments',
                ]
            )
        ;
        if ($this->isAdminUser()) {
            $filter
                ->add(
                    'phone',
                    null,
                    [
                        'label' => 'backend.admin.student.phone',
                    ]
                )
                ->add(
                    'email',
                    null,
                    [
                        'label' => 'backend.admin.student.email',
                    ]
                )
            ;
        }
        $filter
            ->add(
                'address',
                null,
                [
                    'label' => 'backend.admin.student.address',
                ]
            )
            ->add(
                'city',
                null,
                [
                    'label' => 'backend.admin.student.city',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'class' => City::class,
                        'query_builder' => $this->em->getRepository(City::class)->getEnabledSortedByNameQB(),
                    ],
                ]
            )
            ->add(
                'payment',
                null,
                [
                    'label' => 'backend.admin.parent.payment',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => StudentPaymentEnum::getEnumArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'parent.payment',
                null,
                [
                    'label' => 'backend.admin.student.parent_payment',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => StudentPaymentEnum::getEnumArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'bankCreditorSepa',
                null,
                [
                    'label' => 'backend.admin.bank.creditor_bank_name',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'class' => BankCreditorSepa::class,
                        'query_builder' => $this->em->getRepository(BankCreditorSepa::class)->getAllSortedByNameQB(),
                    ],
                ]
            )
            ->add(
                'events.group',
                null,
                [
                    'label' => 'backend.admin.event.group',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'class' => ClassGroup::class,
                        'query_builder' => $this->em->getRepository(ClassGroup::class)->getEnabledSortedByCodeQB(),
                    ],
                ]
            )
            ->add(
                'hasAtLeastOneEventClassGroupAssigned',
                CallbackFilter::class,
                [
                    'label' => 'backend.admin.class_group.has_at_least_one_event_class_group_assigned',
                    'callback' => [$this, 'buildDatagridHasAtLeastOneEventClassGroupAssignedFilter'],
                    'required' => true,
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => SchoolYearChoicesGeneratorEnum::getSchoolYearChoicesArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'schoolYear',
                CallbackFilter::class,
                [
                    'label' => 'backend.admin.class_group.school_year',
                    'callback' => [$this, 'buildDatagridSchoolYearFilter'],
                    'required' => true,
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => SchoolYearChoicesGeneratorEnum::getSchoolYearChoicesArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'age',
                CallbackFilter::class,
                [
                    'label' => 'backend.admin.student.age',
                    'callback' => [$this, 'buildDatagridAgesFilter'],
                    'required' => true,
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => StudentAgesEnum::getReversedEnumTranslatedArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'birthDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.student.birthDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'dischargeDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.student.dischargeDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'unsubscriptionDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.student.unsubscriptionDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'schedule',
                null,
                [
                    'label' => 'backend.admin.student.schedule',
                ]
            )
            ->add(
                'tariff',
                null,
                [
                    'label' => 'backend.admin.student.tariff',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'required' => false,
                        'class' => Tariff::class,
                        'query_builder' => $this->em->getRepository(Tariff::class)->findAllSortedByYearAndPriceQB(),
                    ],
                ]
            )
            ->add(
                'trainingCenter',
                null,
                [
                    'label' => 'backend.admin.class_group.training_center',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'class' => TrainingCenter::class,
                        'query_builder' => $this->em->getRepository(TrainingCenter::class)->getEnabledSortedByNameQB(),
                    ],
                ]
            )
            ->add(
                'hasImageRightsAccepted',
                null,
                [
                    'label' => 'backend.admin.imagerigths.checkbox_label',
                ]
            )
            ->add(
                'hasSepaAgreementAccepted',
                null,
                [
                    'label' => 'backend.admin.sepaagreement.checkbox_label',
                ]
            )
            ->add(
                'isPaymentExempt',
                null,
                [
                    'label' => 'backend.admin.student.is_payment_excempt',
                ]
            )
            ->add(
                'hasAcceptedInternalRegulations',
                null,
                [
                    'label' => 'backend.admin.internalregulations.checkbox_label',
                ]
            )
            ->add(
                'enabled',
                null,
                [
                    'label' => 'backend.admin.enabled',
                ]
            )
        ;
    }

    public function buildDatagridHasAtLeastOneEventClassGroupAssignedFilter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if ('hasAtLeastOneEventClassGroupAssigned' === $field && $data->hasValue()) {
            $beginEndSchoolYearMoment = new BeginEndSchoolYearMoment((int) $data->getValue());
            $relatedEntitiesQuery = $query->getQueryBuilder()->getEntityManager()->getRepository(Student::class)
                ->createQueryBuilder('student')
                ->select(['student.id'])
                ->leftJoin('student.events', 'e')
                ->andWhere('e.begin > :begin')
                ->andWhere('e.begin < :end')
                ->setParameter('begin', $beginEndSchoolYearMoment->getBegin())
                ->setParameter('end', $beginEndSchoolYearMoment->getEnd())
            ;
            $query->andWhere($query->expr()->notIn($alias.'.id', ':subquery'));
            $query->setParameter('subquery', $relatedEntitiesQuery->getQuery()->getArrayResult());

            return true;
        }

        return false;
    }

    public function buildDatagridSchoolYearFilter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if ('schoolYear' === $field && $data->hasValue()) {
            $beginEndSchoolYearMoment = new BeginEndSchoolYearMoment((int) $data->getValue());
            $query->leftJoin($alias.'.events', 'ev');
            $query->andWhere('ev.begin > :begin');
            $query->andWhere('ev.begin < :end');
            $query->setParameter('begin', $beginEndSchoolYearMoment->getBegin());
            $query->setParameter('end', $beginEndSchoolYearMoment->getEnd());

            return true;
        }

        return false;
    }

    public function buildDatagridAgesFilter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if ('age' === $field && $data->hasValue()) {
            $age = (int) $data->getValue();
            if ($age < StudentAgesEnum::AGE_20_plus) {
                $query->andWhere('TIMESTAMPDIFF(year, '.$alias.'.birthDate, NOW()) = :age');
            } else {
                $query->andWhere('TIMESTAMPDIFF(year, '.$alias.'.birthDate, NOW()) >= :age');
            }
            $query->setParameter('age', $age);

            return true;
        }

        return false;
    }

    protected function configureQuery(\Sonata\AdminBundle\Datagrid\ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = current($query->getRootAliases());
        $query
            ->addSelect(PersonRepository::ALIAS)
            ->addSelect(BankRepository::ALIAS)
            ->addSelect(EventRepository::ALIAS)
            ->leftJoin(sprintf('%s.parent', $rootAlias), PersonRepository::ALIAS)
            ->leftJoin(sprintf('%s.bank', $rootAlias), BankRepository::ALIAS)
            ->leftJoin(sprintf('%s.events', $rootAlias), EventRepository::ALIAS)
        ;

        return $query;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add(
                'name',
                null,
                [
                    'label' => 'backend.admin.student.name',
                    'editable' => true,
                ]
            )
            ->add(
                'surname',
                null,
                [
                    'label' => 'backend.admin.student.surname',
                    'editable' => true,
                ]
            )
        ;
        if ($this->isAdminUser()) {
            $list
                ->add(
                    'phone',
                    null,
                    [
                        'label' => 'backend.admin.student.phone',
                        'editable' => true,
                    ]
                )
                ->add(
                    'email',
                    null,
                    [
                        'label' => 'backend.admin.student.email',
                        'editable' => true,
                    ]
                )
            ;
        }
        $list
            ->add(
                'hasImageRightsAccepted',
                null,
                [
                    'label' => 'backend.admin.imagerigths.checkbox_label',
                    'editable' => true,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'hasSepaAgreementAccepted',
                null,
                [
                    'label' => 'backend.admin.sepaagreement.checkbox_label',
                    'editable' => true,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'hasAcceptedInternalRegulations',
                null,
                [
                    'label' => 'backend.admin.internalregulations.checkbox_label',
                    'editable' => true,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'enabled',
                null,
                [
                    'label' => 'backend.admin.enabled',
                    'editable' => true,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                ListMapper::NAME_ACTIONS,
                null,
                [
                    'label' => 'backend.admin.actions',
                    'header_style' => 'width:152px',
                    'header_class' => 'text-right',
                    'row_align' => 'right',
                    'actions' => [
                        'edit' => [
                            'template' => 'Admin/Buttons/list__action_edit_button.html.twig',
                        ],
                        'show' => [
                            'template' => 'Admin/Buttons/list__action_show_button.html.twig',
                        ],
                        'imagerights' => [
                            'template' => 'Admin/Cells/list__action_image_rights.html.twig',
                        ],
                        'sepaagreement' => [
                            'template' => 'Admin/Cells/list__action_sepa_agreement.html.twig',
                        ],
                        'delete' => [
                            'template' => 'Admin/Buttons/list__action_delete_student_button.html.twig',
                        ],
                    ],
                ]
            )
        ;
    }

    public function configureExportFields(): array
    {
        $result = [
            'dni',
            'name',
            'surname',
            'parent.fullCanonicalName',
            'comments',
            'phone',
            'email',
            'address',
            'city',
            'paymentString',
            'bank.name',
            'bank.swiftCode',
            'bank.accountNumber',
            'bankCreditorSepa.name',
            'bankCreditorSepa.iban',
            'birthDateString',
            'dischargeDateString',
            'unsubscriptionDateString',
            'schedule',
            'tariff',
            'trainingCenter',
            'hasImageRightsAccepted',
            'hasSepaAgreementAccepted',
            'hasAcceptedInternalRegulations',
            'enabled',
        ];
        if (!$this->isAdminUser()) {
            unset($result[5], $result[6]);
        }

        return $result;
    }

    /**
     * @throws ModelManagerThrowable
     */
    public function preRemove($object): void
    {
        $relatedReceipts = $this->getModelManager()->findBy(Receipt::class, [
            'student' => $object,
        ]);
        /** @var Receipt $relatedReceipt */
        foreach ($relatedReceipts as $relatedReceipt) {
            $this->getModelManager()->delete($relatedReceipt);
        }
        $relatedInvoices = $this->getModelManager()->findBy(Invoice::class, [
            'student' => $object,
        ]);
        /** @var Invoice $relatedInvoice */
        foreach ($relatedInvoices as $relatedInvoice) {
            $this->getModelManager()->delete($relatedInvoice);
        }
    }

    /**
     * @param Student $object
     */
    public function prePersist($object): void
    {
        $this->commonPreActions($object);
    }

    /**
     * @param Student $object
     */
    public function preUpdate($object): void
    {
        $this->commonPreActions($object);
    }

    private function commonPreActions($object): void
    {
        if ($object->getBank()->getAccountNumber()) {
            $object->getBank()->setAccountNumber(strtoupper($object->getBank()->getAccountNumber()));
        }
        if ($object->getBank()->getSwiftCode()) {
            $object->getBank()->setSwiftCode(strtoupper($object->getBank()->getSwiftCode()));
        }
    }
}
