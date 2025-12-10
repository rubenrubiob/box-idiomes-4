<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Enum\StudentPaymentEnum;
use App\Enum\UserRolesEnum;
use App\Kernel;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class InvoiceAdminController extends AbstractAdminController
{
    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function pdfAction(Request $request, ParameterBagInterface $parameterBag): Response
    {
        /** @var Invoice $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $pdf = $this->ibp->build($object);

        return new Response($pdf->Output(sprintf(
            '%s_invoice_%s.pdf',
            $parameterBag->get('project_export_filename'),
            $object->getSluggedInvoiceNumber()
        )), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function sendAction(Request $request): RedirectResponse
    {
        /** @var Invoice $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $object
            ->setIsSended(true)
            ->setSendDate(new \DateTimeImmutable())
        ;
        $this->mr->getManager()->flush();
        $pdf = $this->ibp->build($object);
        $result = $this->ns->sendInvoicePdfNotification($object, $pdf);
        if (0 === $result) {
            $this->addFlash('danger', 'S\'ha produït un error durant l\'enviament de la factura núm. '.$object->getInvoiceNumber().'. La persona '.$object->getMainEmailName().' no ha rebut cap missatge a la seva bústia.');
        } else {
            $this->addFlash('success', 'S\'ha enviat la factura núm. '.$object->getInvoiceNumber().' amb PDF a la bústia '.$object->getMainEmail());
        }

        return $this->redirectToList();
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function generateDirectDebitAction(Request $request): Response
    {
        /** @var Invoice $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $paymentUniqueId = uniqid('', true);
        $xml = $this->xsbs->buildDirectDebitSingleInvoiceXml($paymentUniqueId, new \DateTime('now + 3 days'), $object);
        $object
            ->setIsSepaXmlGenerated(true)
            ->setSepaXmlGeneratedDate(new \DateTimeImmutable())
        ;
        $this->mr->getManager()->flush();
        if (Kernel::ENV_DEV === $this->getParameter('kernel.environment')) {
            return new Response($xml, Response::HTTP_OK, ['Content-type' => 'application/xml']);
        }
        $now = new \DateTimeImmutable();
        $fileSystem = new Filesystem();
        $fileNamePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'SEPA_invoice_'.$now->format('Y-m-d_H-i').'.xml';
        $fileSystem->touch($fileNamePath);
        $fileSystem->dumpFile($fileNamePath, $xml);
        $response = new BinaryFileResponse($fileNamePath, Response::HTTP_OK, ['Content-type' => 'application/xml']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function duplicateAction(Request $request): RedirectResponse
    {
        /** @var Invoice $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        // new invoice
        $today = new \DateTimeImmutable();
        $newInvoice = new Invoice();
        $newInvoice
            ->setDate($today)
            ->setTrainingCenter($object->getTrainingCenter())
            ->setStudent($object->getStudent())
            ->setPerson($object->getPerson())
            ->setBaseAmount($object->getBaseAmount())
            ->setTotalAmount($object->getTotalAmount())
            ->setDiscountApplied($object->isDiscountApplied())
            ->setMonth((int) $today->format('m'))
            ->setYear((int) $today->format('Y'))
            ->setIsForPrivateLessons($object->getIsForPrivateLessons())
            ->setTaxPercentage($object->getTaxPercentage())
            ->setIrpfPercentage($object->getIrpfPercentage())
            ->setIsPayed(false)
        ;
        $this->mr->getManager()->persist($newInvoice);
        $this->mr->getManager()->flush();
        /** @var InvoiceLine $line */
        foreach ($object->getLines() as $line) {
            $newInvoiceLine = new InvoiceLine();
            $newInvoiceLine
                ->setInvoice($newInvoice)
                ->setDescription($line->getDescription())
                ->setUnits($line->getUnits())
                ->setPriceUnit($line->getPriceUnit())
                ->setDiscount($line->getDiscount())
                ->setTotal($line->getTotal())
            ;
            $this->mr->getManager()->persist($newInvoiceLine);
        }
        $this->mr->getManager()->flush();
        $this->addFlash('success', 'S\'ha duplicat la factura núm. '.$object->getId().' amb la factura núm. '.$newInvoice->getId().' correctament.');

        return $this->redirectToList();
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function batchActionGeneratesepaxmls(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $query->execute();
        try {
            $paymentUniqueId = uniqid('', true);
            $xmls = $this->xsbs->buildDirectDebitInvoicesXml($paymentUniqueId, new \DateTime('now + 3 days'), $selectedModels);
            /** @var Invoice $selectedModel */
            foreach ($selectedModels as $selectedModel) {
                if (StudentPaymentEnum::BANK_ACCOUNT_NUMBER === $selectedModel->getMainSubject()->getPayment() && !$selectedModel->getStudent()?->getIsPaymentExempt()) {
                    $selectedModel
                        ->setIsSepaXmlGenerated(true)
                        ->setSepaXmlGeneratedDate(new \DateTimeImmutable())
                    ;
                }
            }
            $this->mr->getManager()->flush();
            if (Kernel::ENV_DEV === $this->getParameter('kernel.environment')) {
                return new Response($xmls, Response::HTTP_OK, ['Content-type' => 'application/xml']);
            }
            $now = new \DateTimeImmutable();
            $fileSystem = new Filesystem();
            $fileNamePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'SEPA_invoices_'.$now->format('Y-m-d_H-i').'.xml';
            $fileSystem->touch($fileNamePath);
            $fileSystem->dumpFile($fileNamePath, $xmls);
            $response = new BinaryFileResponse($fileNamePath, Response::HTTP_OK, ['Content-type' => 'application/xml']);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'S\'ha produït un error al generar l\'arxiu SEPA amb format XML. Revisa les factures seleccionades.');
            $this->addFlash('error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters(),
                ])
            );
        }
    }
}
