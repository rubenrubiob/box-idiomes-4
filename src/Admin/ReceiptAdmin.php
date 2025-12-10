<?php

namespace App\Admin;

use App\Doctrine\Enum\SortOrderTypeEnum;
use App\Entity\AbstractBase;
use App\Entity\Person;
use App\Entity\Receipt;
use App\Entity\Student;
use App\Entity\TrainingCenter;
use App\Enum\InvoiceYearMonthEnum;
use App\Enum\StudentPaymentEnum;
use App\Repository\PersonRepository;
use App\Repository\StudentRepository;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class ReceiptAdmin extends AbstractBaseAdmin
{
    protected $classnameLabel = 'Receipt';

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::PER_PAGE] = 500;
        $sortValues[DatagridInterface::SORT_ORDER] = SortOrderTypeEnum::DESC;
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    public function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'billings/receipt';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->add('generate')
            ->add('creator')
            ->add('createInvoice', $this->getRouterIdParameter().'/create-invoice')
            ->add('reminder', $this->getRouterIdParameter().'/reminder-pdf')
            ->add('sendReminder', $this->getRouterIdParameter().'/reminder-send')
            ->add('pdf', $this->getRouterIdParameter().'/pdf')
            ->add('send', $this->getRouterIdParameter().'/send')
            ->add('generateDirectDebit', $this->getRouterIdParameter().'/generate-direct-debit-xml')
            ->remove('show')
        ;
    }

    public function configureBatchActions(array $actions): array
    {
        if ($this->hasRoute('edit') && $this->hasAccess('edit')) {
            $actions['generatereminderspdf'] = [
                'label' => 'backend.admin.receipt_reminder.batch_action',
                'translation_domain' => 'messages',
                'ask_confirmation' => false,
            ];
            $actions['generatesepaxmls'] = [
                'label' => 'backend.admin.invoice.batch_action',
                'translation_domain' => 'messages',
                'ask_confirmation' => false,
            ];
            $actions['markaspayed'] = [
                'label' => 'backend.admin.receipt.mark_as_payed_batch_action',
                'translation_domain' => 'messages',
                'ask_confirmation' => false,
            ];
        }

        return $actions;
    }

    public function configureDashboardActions(array $actions): array
    {
        $actions['generate'] = [
            'label' => 'backend.admin.receipt.generate_batch',
            'translation_domain' => 'messages',
            'url' => $this->generateUrl('generate'),
            'icon' => 'inbox',
        ];

        return $actions;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $now = new \DateTimeImmutable();
        $currentYear = $now->format('Y');
        $form
            ->with('backend.admin.receipt.receipt', $this->getFormMdSuccessBoxArray('backend.admin.receipt.receipt', 3))
            ->add(
                'year',
                ChoiceType::class,
                [
                    'label' => 'backend.admin.invoice.year',
                    'required' => true,
                    'choices' => InvoiceYearMonthEnum::getYearEnumArray(),
                    'preferred_choices' => [$currentYear],
                ]
            )
            ->add(
                'month',
                ChoiceType::class,
                [
                    'label' => 'backend.admin.invoice.month',
                    'required' => true,
                    'choices' => InvoiceYearMonthEnum::getMonthEnumArray(),
                ]
            )
            ->add(
                'student',
                EntityType::class,
                [
                    'label' => 'backend.admin.invoice.student',
                    'required' => true,
                    'class' => Student::class,
                    'choice_label' => 'fullCanonicalName',
                    'query_builder' => $this->em->getRepository(Student::class)->getEnabledSortedBySurnameValidTariffQB(),
                ]
            )
            ->add(
                'person',
                EntityType::class,
                [
                    'label' => 'backend.admin.invoice.person',
                    'required' => false,
                    'class' => Person::class,
                    'choice_label' => 'fullCanonicalName',
                    'query_builder' => $this->em->getRepository(Person::class)->getEnabledSortedBySurnameQB(),
                    'disabled' => true,
                ]
            )
            ->end()
            ->with('backend.admin.invoice.detail', $this->getFormMdSuccessBoxArray('backend.admin.invoice.detail', 3))
            ->add(
                'date',
                DatePickerType::class,
                [
                    'label' => 'backend.admin.receipt.date',
                    'format' => 'd/M/y',
                    'required' => $this->isFormToCreateNewRecord(),
                    'disabled' => !$this->isFormToCreateNewRecord(),
                ]
            )
            ->add(
                'discountApplied',
                null,
                [
                    'label' => 'backend.admin.invoice.discountApplied',
                    'required' => false,
                    'disabled' => true,
                ]
            )
            ->add(
                'baseAmount',
                null,
                [
                    'label' => 'backend.admin.invoice.baseAmount',
                    'required' => false,
                    'disabled' => true,
                ]
            )
            ->end()
            ->with('backend.admin.controls', $this->getFormMdSuccessBoxArray('backend.admin.controls', 3))
            ->add(
                'trainingCenter',
                EntityType::class,
                [
                    'label' => 'backend.admin.class_group.training_center',
                    'required' => true,
                    'class' => TrainingCenter::class,
                    'query_builder' => $this->em->getRepository(TrainingCenter::class)->getEnabledSortedByNameQB(),
                ]
            )
            ->add(
                'isForPrivateLessons',
                CheckboxType::class,
                [
                    'label' => 'backend.admin.is_for_private_lessons',
                    'required' => false,
                    'disabled' => false,
                ]
            )
        ;
        if ($this->isFormToCreateNewRecord() || (!$this->isFormToCreateNewRecord() && !$this->getSubject()?->getStudent()->isPaymentExempt())) {
            $form
                ->add(
                    'isSepaXmlGenerated',
                    CheckboxType::class,
                    [
                        'label' => 'backend.admin.receipt.isSepaXmlGenerated',
                        'required' => false,
                        'disabled' => true,
                    ]
                )
                ->add(
                    'sepaXmlGeneratedDate',
                    DatePickerType::class,
                    [
                        'label' => 'backend.admin.receipt.sepaXmlGeneratedDate',
                        'format' => 'd/M/y',
                        'required' => false,
                        'disabled' => true,
                    ]
                )
                ->add(
                    'isSended',
                    CheckboxType::class,
                    [
                        'label' => 'backend.admin.receipt.isSended',
                        'required' => false,
                        'disabled' => true,
                    ]
                )
                ->add(
                    'sendDate',
                    DatePickerType::class,
                    [
                        'label' => 'backend.admin.invoice.sendDate',
                        'format' => 'd/M/y',
                        'required' => false,
                        'disabled' => true,
                    ]
                )
                ->add(
                    'isPayed',
                    CheckboxType::class,
                    [
                        'label' => 'backend.admin.receipt.isPayed',
                        'required' => false,
                    ]
                )
                ->add(
                    'paymentDate',
                    DatePickerType::class,
                    [
                        'label' => 'backend.admin.invoice.paymentDate',
                        'format' => 'd/M/y',
                        'required' => false,
                    ]
                )
            ;
        }
        $form->end();
        if ($this->id($this->getSubject())) { // is edit mode, disable on new subjetcs
            $form
                ->with('backend.admin.receipt.lines', $this->getFormMdSuccessBoxArray('backend.admin.receipt.lines', 12))
                ->add(
                    'lines',
                    CollectionType::class,
                    [
                        'label' => 'backend.admin.invoice.line',
                        'required' => true,
                        'error_bubbling' => true,
                        'by_reference' => false,
                    ],
                    [
                        'edit' => 'inline',
                        'inline' => 'table',
                    ]
                )
                ->end()
            ;
        }
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add(
                'id',
                null,
                [
                    'label' => 'backend.admin.receipt.id',
                ]
            )
            ->add(
                'date',
                DateFilter::class,
                [
                    'label' => 'backend.admin.receipt.date',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'year',
                null,
                [
                    'label' => 'backend.admin.invoice.year',
                ]
            )
            ->add(
                'month',
                null,
                [
                    'label' => 'backend.admin.invoice.month',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => InvoiceYearMonthEnum::getMonthEnumArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'student',
                ModelFilter::class,
                [
                    'label' => 'backend.admin.invoice.student',
                    'field_type' => ModelAutocompleteType::class,
                    'field_options' => [
                        'class' => Student::class,
                        'property' => ['name', 'surname'],
                    ],
                ]
            )
            ->add(
                'person',
                ModelFilter::class,
                [
                    'label' => 'backend.admin.invoice.person',
                    'field_type' => ModelAutocompleteType::class,
                    'field_options' => [
                        'class' => Person::class,
                        'property' => ['name', 'surname'],
                    ],
                ]
            )
            ->add(
                'student.payment',
                null,
                [
                    'label' => 'backend.admin.student.payment',
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => StudentPaymentEnum::getEnumArray(),
                        'expanded' => false,
                        'multiple' => false,
                    ],
                ]
            )
            ->add(
                'person.payment',
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
                'discountApplied',
                null,
                [
                    'label' => 'backend.admin.invoice.discountApplied',
                ]
            )
            ->add(
                'baseAmount',
                null,
                [
                    'label' => 'backend.admin.invoice.baseAmount',
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
                'isForPrivateLessons',
                null,
                [
                    'label' => 'backend.admin.is_for_private_lessons',
                ]
            )
            ->add(
                'isSepaXmlGenerated',
                null,
                [
                    'label' => 'backend.admin.receipt.isSepaXmlGenerated',
                ]
            )
            ->add(
                'sepaXmlGeneratedDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.receipt.sepaXmlGeneratedDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'isSended',
                null,
                [
                    'label' => 'backend.admin.receipt.isSended',
                ]
            )
            ->add(
                'sendDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.invoice.sendDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
            ->add(
                'isPayed',
                null,
                [
                    'label' => 'backend.admin.receipt.isPayed',
                ]
            )
            ->add(
                'paymentDate',
                DateFilter::class,
                [
                    'label' => 'backend.admin.invoice.paymentDate',
                    'field_type' => DatePickerType::class,
                    'field_options' => [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                    ],
                ]
            )
        ;
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $rootAlias = current($query->getRootAliases());
        $query
            ->addSelect(StudentRepository::ALIAS)
            ->addSelect(PersonRepository::ALIAS)
//            ->addSelect(sprintf('COUNT(%s.id) AS numberoflines', ReceiptLineRepository::ALIAS))
            ->leftJoin(sprintf('%s.student', $rootAlias), StudentRepository::ALIAS)
            ->leftJoin(sprintf('%s.person', $rootAlias), PersonRepository::ALIAS)
//            ->leftJoin(sprintf('%s.lines', $rootAlias), ReceiptLineRepository::ALIAS)
//            ->addGroupBy($rootAlias)
        ;

        return $query;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add(
                'numberoflines',
                null,
                [
                    'label' => 'backend.admin.receipt.id',
                    'virtual_field' => true,
                ]
            )
            ->add(
                'id',
                null,
                [
                    'label' => 'backend.admin.receipt.id',
                    'template' => 'Admin/Cells/list__cell_receipt_number.html.twig',
                ]
            )
            ->add(
                'date',
                FieldDescriptionInterface::TYPE_DATE,
                [
                    'label' => 'backend.admin.receipt.date',
                    'format' => AbstractBase::DATE_STRING_FORMAT,
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'year',
                null,
                [
                    'label' => 'backend.admin.invoice.year',
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'month',
                null,
                [
                    'label' => 'backend.admin.invoice.month',
                    'template' => 'Admin/Cells/list__cell_event_month.html.twig',
                ]
            )
            ->add(
                'student',
                null,
                [
                    'label' => 'backend.admin.invoice.student',
                    'editable' => false,
                    'associated_property' => 'fullCanonicalName',
                    'sortable' => true,
                    'sort_field_mapping' => ['fieldName' => 'surname'],
                    'sort_parent_association_mappings' => [['fieldName' => 'student']],
                ]
            )
            ->add(
                'baseAmount',
                null,
                [
                    'label' => 'backend.admin.invoice.baseAmount',
                    'template' => 'Admin/Cells/list__cell_receipt_amount.html.twig',
                    'editable' => false,
                    'header_class' => 'text-right',
                    'row_align' => 'right',
                ]
            )
            ->add(
                'isForPrivateLessons',
                null,
                [
                    'label' => 'backend.admin.is_for_private_lessons',
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'isSepaXmlGenerated',
                null,
                [
                    'label' => 'backend.admin.receipt.isSepaXmlGenerated',
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'isSended',
                null,
                [
                    'label' => 'backend.admin.receipt.isSended',
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                'isPayed',
                null,
                [
                    'label' => 'backend.admin.receipt.isPayed',
                    'editable' => false,
                    'header_class' => 'text-center',
                    'row_align' => 'center',
                ]
            )
            ->add(
                ListMapper::NAME_ACTIONS,
                ListMapper::TYPE_ACTIONS,
                [
                    'label' => 'backend.admin.actions',
                    'header_style' => 'width:248px',
                    'header_class' => 'text-right',
                    'row_align' => 'right',
                    'actions' => [
                        'edit' => [
                            'template' => 'Admin/Buttons/list__action_edit_button.html.twig',
                        ],
                        'reminder' => [
                            'template' => 'Admin/Buttons/list__action_receipt_reminder_button.html.twig',
                        ],
                        'sendReminder' => [
                            'template' => 'Admin/Buttons/list__action_receipt_reminder_send_button.html.twig',
                        ],
                        'pdf' => [
                            'template' => 'Admin/Buttons/list__action_receipt_pdf_button.html.twig',
                        ],
                        'send' => [
                            'template' => 'Admin/Buttons/list__action_receipt_send_button.html.twig',
                        ],
                        'createInvoice' => [
                            'template' => 'Admin/Buttons/list__action_receipt_create_invoice_button.html.twig',
                        ],
                        'generateDirectDebit' => [
                            'template' => 'Admin/Buttons/list__action_generate_direct_debit_xml_button.html.twig',
                        ],
                        'delete' => [
                            'template' => 'Admin/Buttons/list__action_delete_button.html.twig',
                        ],
                    ],
                ]
            );
    }

    public function configureExportFields(): array
    {
        return [
            'receiptNumber',
            'dateString',
            'year',
            'month',
            'student.fullCanonicalName',
            'person.fullCanonicalName',
            'student.paymentString',
            'discountAppliedString',
            'baseAmountString',
            'trainingCenter',
            'isForPrivateLessonsString',
            'isSepaXmlGeneratedString',
            'sepaXmlGeneratedDateString',
            'isSendedString',
            'sendDateString',
            'isPayedString',
            'paymentDateString',
        ];
    }

    /**
     * @param Receipt $object
     */
    public function prePersist($object): void
    {
        $this->commonPreActions($object);
    }

    /**
     * @param Receipt $object
     */
    public function preUpdate($object): void
    {
        $this->commonPreActions($object);
    }

    private function commonPreActions($object): void
    {
        if ($object->getStudent()->getParent()) {
            $object->setPerson($object->getStudent()->getParent());
        }
        $object->setBaseAmount($object->calculateTotalBaseAmount());
    }
}
