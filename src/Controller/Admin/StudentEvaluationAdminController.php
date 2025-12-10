<?php

namespace App\Controller\Admin;

use App\Entity\StudentEvaluation;
use App\Enum\StudentEvaluationEnum;
use App\Enum\UserRolesEnum;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class StudentEvaluationAdminController extends AbstractAdminController
{
    #[IsGranted(UserRolesEnum::ROLE_MANAGER)]
    public function previewAction(Request $request, ParameterBagInterface $parameterBag, SluggerInterface $slugger): Response
    {
        /** @var StudentEvaluation $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $pdf = $this->sebp->build($object);

        return new Response($pdf->Output($parameterBag->get('project_export_filename').'_evaluation_'.$slugger->slug($object->getFullCourseAsString()).'.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    #[IsGranted(UserRolesEnum::ROLE_MANAGER)]
    public function notificationAction(Request $request): RedirectResponse
    {
        /** @var StudentEvaluation $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $object
            ->setHasBeenNotified(true)
            ->setNotificationDate(new \DateTimeImmutable())
        ;
        $this->mr->getManager()->flush();
        $pdf = $this->sebp->build($object);
        $result = $this->ns->sendStudentEvaluationPdfNotification($object, $pdf);
        if (0 === $result) {
            $this->addFlash(
                'danger',
                sprintf(
                    'S\'ha produït un error durant l\'enviament a l\'adreça %s de l\'avaluació %s del %s de l\'alumne %s en PDF.',
                    $object->getStudent()->getMainEmailSubject(),
                    $object->getFullCourseAsString(),
                    $this->ts->trans(StudentEvaluationEnum::getReversedEnumArray()[$object->getEvaluation()]),
                    $object->getStudent()->getFullName()
                )
            );
        } else {
            $this->addFlash(
                'success',
                sprintf(
                    'S\'ha enviat un correu electrònic a l\'adreça %s amb l\'avaluació %s del %s de l\'alumne %s en PDF.',
                    $object->getStudent()->getMainEmailSubject(),
                    $object->getFullCourseAsString(),
                    $this->ts->trans(StudentEvaluationEnum::getReversedEnumArray()[$object->getEvaluation()]),
                    $object->getStudent()->getFullName()
                )
            );
        }

        return $this->redirectToList();
    }
}
