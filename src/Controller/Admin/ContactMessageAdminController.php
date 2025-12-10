<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Form\Type\ContactMessageAnswerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContactMessageAdminController extends AbstractAdminController
{
    public function showAction(Request $request): Response
    {
        /** @var ContactMessage $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $preResponse = $this->preShow($request, $object);
        if (null !== $preResponse) {
            return $preResponse;
        }
        $this->admin->setSubject($object);
        $object->setChecked(true);
        $this->mr->getManager()->persist($object);
        $this->mr->getManager()->flush();

        return $this->render(
            'Admin/show.html.twig',
            [
                'action' => 'show',
                'object' => $object,
                'elements' => $this->admin->getShow(),
            ]
        );
    }

    /**
     * Answer message action.
     */
    public function answerAction(Request $request): Response
    {
        /** @var ContactMessage $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $form = $this->createForm(ContactMessageAnswerType::class, $object);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // persist new contact message form record
            $object->setChecked(true);
            $object->setAnswered(true);
            $this->mr->getManager()->persist($object);
            $this->mr->getManager()->flush();
            // send notifications
            $this->ns->sendUserBackendNotification($object);
            // build flash message
            $this->addFlash('success', $this->ts->trans('frontend.index.main.sent'));

            return $this->redirectToRoute('admin_app_contactmessage_list');
        }

        return $this->render(
            'Admin/ContactMessage/answer_form.html.twig',
            [
                'action' => 'answer',
                'object' => $object,
                'form' => $form,
                'elements' => $this->admin->getShow(),
            ]
        );
    }
}
