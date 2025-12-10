<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BackendInvoiceMenuBuilder
{
    private FactoryInterface $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function createSideMenu(RequestStack $requestStack): ItemInterface
    {
        $route = null;
        if ($requestStack->getCurrentRequest()) {
            $route = $requestStack->getCurrentRequest()->attributes->get('_route');
        }
        $menu = $this->factory->createItem('Facturació');
        $menu
            ->addChild(
                'tariffs',
                [
                    'label' => 'backend.admin.student.tariff',
                    'route' => 'admin_app_tariff_list',
                    'current' => 'admin_app_tariff_list' === $route || 'admin_app_tariff_create' === $route || 'admin_app_tariff_edit' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'receipts',
                [
                    'label' => 'backend.admin.receipt.receipt',
                    'route' => 'admin_app_receipt_list',
                    'current' => 'admin_app_receipt_list' === $route || 'admin_app_receipt_create' === $route || 'admin_app_receipt_edit' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'generator',
                [
                    'label' => 'backend.admin.receipt.generate_batch',
                    'route' => 'admin_app_receipt_generate',
                    'current' => 'admin_app_receipt_generate' === $route,
                ]
            )
            ->setExtras(
                [
                    'icon' => '<i class="fa fa-inbox"></i>',
                ]
            )
        ;
        $menu
            ->addChild(
                'receipt_groups',
                [
                    'label' => 'backend.admin.receipt.receipt_group',
                    'route' => 'admin_app_receiptgroup_list',
                    'current' => 'admin_app_receiptgroup_list' === $route || 'admin_app_receiptgroup_delete' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'invoices',
                [
                    'label' => 'backend.admin.invoice.invoice',
                    'route' => 'admin_app_invoice_list',
                    'current' => 'admin_app_invoice_list' === $route || 'admin_app_invoice_create' === $route || 'admin_app_invoice_edit' === $route,
                ]
            )
        ;

        return $menu;
    }
}
