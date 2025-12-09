<?php

namespace App\Menu;

use App\Enum\UserRolesEnum;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class BackendStudentsMenuBuilder
{
    private FactoryInterface $factory;
    private Security $security;

    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory = $factory;
        $this->security = $security;
    }

    public function createSideMenu(RequestStack $requestStack): ItemInterface
    {
        $route = null;
        if ($requestStack->getCurrentRequest()) {
            $route = $requestStack->getCurrentRequest()->attributes->get('_route');
        }
        $menu = $this->factory->createItem('Alumnes');
        $menu
            ->addChild(
                'students',
                [
                    'label' => 'backend.admin.student.student',
                    'route' => 'admin_app_student_list',
                    'current' => 'admin_app_student_list' === $route || 'admin_app_student_create' === $route || 'admin_app_student_edit' === $route || 'admin_app_student_delete' === $route || 'admin_app_student_show' === $route || 'admin_app_student_imagerights' === $route || 'admin_app_student_sepaagreement' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'evaluations',
                [
                    'label' => 'Avaluacions',
                    'route' => 'admin_app_studentevaluation_list',
                    'current' => 'admin_app_studentevaluation_list' === $route || 'admin_app_studentevaluation_create' === $route || 'admin_app_studentevaluation_edit' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'absences',
                [
                    'label' => 'Absència alumne',
                    'route' => 'admin_app_studentabsence_list',
                    'current' => 'admin_app_studentabsence_list' === $route || 'admin_app_studentabsence_create' === $route || 'admin_app_studentabsence_edit' === $route || 'admin_app_studentabsence_notification' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'persons',
                [
                    'label' => 'backend.admin.student.parent',
                    'route' => 'admin_app_person_list',
                    'current' => 'admin_app_person_list' === $route || 'admin_app_person_create' === $route || 'admin_app_person_edit' === $route,
                ]
            )
        ;
        $menu
            ->addChild(
                'preregisters',
                [
                    'label' => 'Pre Register List',
                    'route' => 'admin_app_preregister_list',
                    'current' => 'admin_app_preregister_list' === $route || 'admin_app_preregister_delete' === $route || 'admin_app_preregister_show' === $route,
                ]
            )
        ;
        if ($this->security->isGranted(UserRolesEnum::ROLE_ADMIN)) {
            $menu
                ->addChild(
                    'mailing',
                    [
                        'label' => 'Students Mailing',
                        'route' => 'admin_app_student_mailing',
                        'current' => 'admin_app_student_mailing' === $route || 'admin_app_student_write_mailing' === $route || 'admin_app_student_deliver_massive_mailing' === $route,
                    ]
                )
                ->setExtras(
                    [
                        'icon' => '<i class="fa fa-bullhorn"></i>',
                    ]
                )
            ;
        }

        return $menu;
    }
}
