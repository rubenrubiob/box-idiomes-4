<?php

namespace App\Controller\Admin;

use App\Entity\Teacher;
use App\Repository\TeacherAbsenceRepository;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TeacherAdminController extends CRUDController
{
    public function detailAction(Request $request, TeacherAbsenceRepository $tar): Response
    {
        /** @var Teacher $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $absences = $tar->getTeacherAbsencesSortedByDate($object);

        return $this->render(
            'Admin/Teacher/detail.html.twig',
            [
                'action' => 'show',
                'object' => $object,
                'absences' => $absences,
                'elements' => $this->admin->getShow(),
            ]
        );
    }
}
