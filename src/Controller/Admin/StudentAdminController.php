<?php

namespace App\Controller\Admin;

use App\Entity\ClassGroup;
use App\Entity\MailingStudentsNotificationMessage;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\TrainingCenter;
use App\Enum\UserRolesEnum;
use App\Form\Model\FilterCalendarEventModel;
use App\Form\Type\FilterStudentsMailingCalendarEventsType;
use App\Form\Type\MailingStudentsNotificationMessageType;
use App\Manager\EventManager;
use App\Message\NewMailingStudentsNotificationMessage;
use App\Pdf\SepaAgreementBuilderPdf;
use App\Pdf\StudentImageRightsBuilderPdf;
use App\Repository\EventRepository;
use App\Repository\MailingStudentsNotificationMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class StudentAdminController extends AbstractAdminController
{
    #[IsGranted(UserRolesEnum::ROLE_MANAGER)]
    public function imagerightsAction(Request $request, StudentImageRightsBuilderPdf $sirps): Response
    {
        /** @var Student $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $pdf = $sirps->build($object);

        return new Response($pdf->Output('student_image_rights_'.$object->getId().'.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    #[IsGranted(UserRolesEnum::ROLE_MANAGER)]
    public function sepaagreementAction(Request $request, SepaAgreementBuilderPdf $saps): Response
    {
        /** @var Student $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $pdf = $saps->build($object);

        return new Response($pdf->Output('sepa_agreement_'.$object->getId().'.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    public function showAction(Request $request): Response
    {
        /** @var Student $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $this->admin->setSubject($object);

        return $this->render(
            'Admin/Student/show.html.twig',
            [
                'action' => 'show',
                'object' => $object,
                'elements' => $this->admin->getShow(),
            ]
        );
    }

    #[IsGranted(UserRolesEnum::ROLE_MANAGER)]
    public function mailingAction(Request $request): Response
    {
        $calendarEventsFilter = new FilterCalendarEventModel();
        if ($request->getSession()->has(FilterStudentsMailingCalendarEventsType::SESSION_KEY)) {
            $calendarEventsFilter = $request->getSession()->get(FilterStudentsMailingCalendarEventsType::SESSION_KEY);
        }
        $calendarEventsFilterForm = $this->createForm(FilterStudentsMailingCalendarEventsType::class, $calendarEventsFilter);
        $calendarEventsFilterForm->handleRequest($request);
        if ($calendarEventsFilterForm->isSubmitted()) {
            $request->getSession()->set(FilterStudentsMailingCalendarEventsType::SESSION_KEY, $calendarEventsFilter);
        }

        return $this->render(
            'Admin/Student/mailing.html.twig',
            [
                'filter' => $calendarEventsFilterForm,
            ]
        );
    }

    public function mailingResetAction(Request $request): Response
    {
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY);
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY_FROM_DATE);
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY_TO_DATE);

        return $this->redirectToRoute('admin_app_student_mailing');
    }

    public function writeMailingAction(Request $request, EntityManagerInterface $entityManager, EventRepository $er, MailingStudentsNotificationMessageRepository $msnmr, EventManager $em, MessageBusInterface $bus): Response
    {
        $startDate = $request->getSession()->get(FilterStudentsMailingCalendarEventsType::SESSION_KEY_FROM_DATE);
        $endDate = $request->getSession()->get(FilterStudentsMailingCalendarEventsType::SESSION_KEY_TO_DATE);
        $calendarEventsFilter = new FilterCalendarEventModel();
        if ($request->getSession()->has(FilterStudentsMailingCalendarEventsType::SESSION_KEY)) {
            $calendarEventsFilter = $request->getSession()->get(FilterStudentsMailingCalendarEventsType::SESSION_KEY);
            $events = $er->getEnabledFilteredByBeginEndAndFilterCalendarEventForm($startDate, $endDate, $request->getSession()->get(FilterStudentsMailingCalendarEventsType::SESSION_KEY));
        } else {
            $events = $er->getEnabledFilteredByBeginAndEnd($startDate, $endDate);
        }
        $students = $em->getInvolvedUniqueStudentsInsideEventsList($events);
        $mailingStudentsNotificationMessage = new MailingStudentsNotificationMessage();
        $form = $this->createForm(MailingStudentsNotificationMessageType::class, $mailingStudentsNotificationMessage);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mailingStudentsNotificationMessage
                ->setSendDate(new \DateTimeImmutable())
                ->setIsSended(true)
                ->setFilterStartDate($startDate)
                ->setFilterEndDate($endDate)
                ->setFilteredClassroom($calendarEventsFilter->getClassroom())
                ->setTotalTargetStudents(count($students))
            ;
            if ($calendarEventsFilter->getTeacher()) {
                $filteredTeacher = $entityManager->getRepository(Teacher::class)->find($calendarEventsFilter->getTeacher()->getId());
                $mailingStudentsNotificationMessage->setFilteredTeacher($filteredTeacher);
            }
            if ($calendarEventsFilter->getGroup()) {
                $filteredClassGroup = $entityManager->getRepository(ClassGroup::class)->find($calendarEventsFilter->getGroup()->getId());
                $mailingStudentsNotificationMessage->setFilteredClassGroup($filteredClassGroup);
            }
            if ($calendarEventsFilter->getTrainingCenter()) {
                $filteredTrainingCenter = $entityManager->getRepository(TrainingCenter::class)->find($calendarEventsFilter->getTrainingCenter()->getId());
                $mailingStudentsNotificationMessage->setFilteredTrainingCenter($filteredTrainingCenter);
            }
            $entityManager->persist($mailingStudentsNotificationMessage);
            $entityManager->flush();
            /** @var Student $student */
            foreach ($students as $student) {
                $bus->dispatch(new NewMailingStudentsNotificationMessage($student->getId(), $mailingStudentsNotificationMessage->getId()));
            }

            return $this->redirectToRoute('admin_app_student_deliver_massive_mailing');
        }

        return $this->render(
            'Admin/Student/write_mailing.html.twig',
            [
                'form' => $form,
                'calendar_events_filter' => $calendarEventsFilter,
                'students' => $students,
                'today_available_notifications_amount' => $msnmr->getTodayAvailableNotificationsAmount(),
            ]
        );
    }

    public function deliverMassiveMailingAction(Request $request): Response
    {
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY);
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY_FROM_DATE);
        $request->getSession()->remove(FilterStudentsMailingCalendarEventsType::SESSION_KEY_TO_DATE);

        return $this->render('Admin/Student/deliver_massive_mailing.html.twig');
    }

    public function batchActionMarkasinactive(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $query->execute();
        if (count($selectedModels) > 0) {
            $total = 0;
            /** @var Student $selectedModel */
            foreach ($selectedModels as $selectedModel) {
                if ($selectedModel->isEnabled()) {
                    $selectedModel->setEnabled(false);
                    ++$total;
                }
            }
            if ($total > 0) {
                $this->mr->getManager()->flush();
                $this->addFlash('success', 'S\'han marcat com a inactius un total de '.$total.' alumnes.');
            } else {
                $this->addFlash('warning', 'No s\'han modificat cap alumne, tots els alumnes escollits ja estaven marcats com a inactius.');
            }
        }

        return new RedirectResponse(
            $this->admin->generateUrl('list', [
                'filter' => $this->admin->getFilterParameters(),
            ])
        );
    }
}
