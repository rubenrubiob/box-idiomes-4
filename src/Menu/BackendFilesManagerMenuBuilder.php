<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BackendFilesManagerMenuBuilder
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
        $menu = $this->factory->createItem('Fitxers');
        $menu
            ->addChild(
                'files',
                [
                    'label' => 'backend.admin.files',
                    'route' => 'admin_app_filedummy_handler',
                    'current' => 'admin_app_filedummy_handler' === $route || 'file_manager' === $route || 'file_manager_rename' === $route || 'file_manager_upload' === $route,
                ]
            )
        ;

        return $menu;
    }
}
