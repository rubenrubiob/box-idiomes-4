<?php

namespace App\Controller\Admin;

use App\Entity\Spending;
use App\Enum\UserRolesEnum;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SpendingAdminController extends CRUDController
{
    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function duplicateAction(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Spending $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        // new spending
        $newSpending = new Spending();
        $newSpending
            ->setDate(new \DateTimeImmutable())
            ->setCategory($object->getCategory())
            ->setProvider($object->getProvider())
            ->setDescription($object->getDescription())
            ->setBaseAmount($object->getBaseAmount())
            ->setIsPayed(false)
            ->setPaymentMethod($object->getPaymentMethod())
        ;
        $em->persist($newSpending);
        $em->flush();
        $this->addFlash('success', 'S\'ha duplicat la despesa núm. '.$object->getId().' amb la factura núm. '.$newSpending->getId().' correctament.');

        return $this->redirectToList();
    }
}
