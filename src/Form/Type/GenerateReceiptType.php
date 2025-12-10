<?php

namespace App\Form\Type;

use App\Repository\TrainingCenterRepository;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;

class GenerateReceiptType extends GenerateReceiptYearMonthChooserType
{
    public const string NAME = 'generate_receipt';

    private RouterInterface $rs;

    public function __construct(TrainingCenterRepository $tcr, RouterInterface $rs)
    {
        parent::__construct($tcr);
        $this->rs = $rs;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('preview')
            // warning: this collection can produce a large input form fields, consider to increase max_input_vars PHP config
            ->add(
                'items',
                CollectionType::class,
                [
                    'label' => 'backend.admin.invoice.items',
                    'allow_extra_fields' => true,
                    'required' => false,
                    'entry_type' => GenerateReceiptItemType::class,
                    'by_reference' => false,
                    'entry_options' => [
                        'label' => false,
                    ],
                ]
            )
            ->add(
                'generate',
                SubmitType::class,
                [
                    'label' => 'backend.admin.receipt.generate',
                    'attr' => [
                        'class' => 'btn btn-success',
                    ],
                ]
            )
            ->add(
                'generate_and_send',
                SubmitType::class,
                [
                    'label' => 'backend.admin.receipt.generate_and_send',
                    'attr' => [
                        'class' => 'btn btn-success',
                    ],
                ]
            )
            ->setAction($this->rs->generate('admin_app_receipt_creator'))
        ;
    }
}
