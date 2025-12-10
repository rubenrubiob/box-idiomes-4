<?php

namespace App\Controller\Admin;

use App\Entity\BankCreditorSepa;
use App\Entity\Receipt;
use App\Enum\StudentPaymentEnum;
use App\Enum\UserRolesEnum;
use App\Form\Model\GenerateReceiptModel;
use App\Form\Type\GenerateReceiptType;
use App\Form\Type\GenerateReceiptYearMonthChooserType;
use App\Kernel;
use App\Message\NewReceiptGroupCreatedMessage;
use Digitick\Sepa\Exception\InvalidArgumentException;
use Digitick\Sepa\Util\StringHelper;
use PhpZip\ZipFile;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ReceiptAdminController extends AbstractAdminController
{
    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function generateAction(Request $request): Response
    {
        // year & month chooser form
        $generateReceiptYearMonthChooser = new GenerateReceiptModel();
        $yearMonthForm = $this->createForm(GenerateReceiptYearMonthChooserType::class, $generateReceiptYearMonthChooser);
        $yearMonthForm->handleRequest($request);
        // build items form
        $generateReceipt = new GenerateReceiptModel();
        $form = $this->createForm(GenerateReceiptType::class, $generateReceipt);
        $form->handleRequest($request);
        if ($yearMonthForm->isSubmitted() && $yearMonthForm->isValid()) {
            // copy values from first form filters selection
            $generateReceipt
                ->setYear($generateReceiptYearMonthChooser->getYear())
                ->setMonth($generateReceiptYearMonthChooser->getMonth())
                ->setTrainingCenter($generateReceiptYearMonthChooser->getTrainingCenter())
            ;
            // build preview view
            $generateReceipt = $this->grfm->buildFullModelForm($generateReceipt);
            $form = $this->createForm(GenerateReceiptType::class, $generateReceipt);
        }

        return $this->render(
            'Admin/Receipt/generate_receipt_form.html.twig',
            [
                'action' => 'generate',
                'year_month_form' => $yearMonthForm,
                'form' => $form,
            ]
        );
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function creatorAction(Request $request): RedirectResponse
    {
        $generateReceipt = $this->grfm->transformRequestArrayToModel($request->request->all(GenerateReceiptType::NAME));
        if (array_key_exists('generate_and_send', $request->request->all(GenerateReceiptType::NAME))) {
            // generate receipts and send it by email
            $recordsParsed = $this->grfm->persistAndDeliverFullModelForm($generateReceipt);
        } else {
            // only generate receipts
            $recordsParsed = $this->grfm->persistFullModelForm($generateReceipt);
        }
        if (0 === $recordsParsed) {
            $this->addFlash('danger', $this->ts->trans('backend.admin.receipt.generator.no_records_presisted'));
        } else {
            $this->addFlash('success', $this->ts->trans('backend.admin.receipt.generator.flash_success', ['%amount%' => $recordsParsed], 'messages'));
        }

        return $this->redirectToList();
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function createInvoiceAction(Request $request): Response
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $invoice = $this->rm->createInvoiceFromReceipt($object);
        $this->mr->getManager()->persist($invoice);
        $this->mr->getManager()->flush();
        $this->addFlash('success', 'S\'ha generat la factura núm. '.$invoice->getInvoiceNumber());

        return $this->redirectToList();
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function reminderAction(Request $request, ParameterBagInterface $parameterBag): Response
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        if (StudentPaymentEnum::BANK_ACCOUNT_NUMBER === $object->getMainSubject()->getPayment()) {
            throw $this->createNotFoundException(sprintf('invalid payment type for object with id: %s', $object->getId()));
        }
        $pdf = $this->rbp->build($object);

        return new Response($pdf->Output($parameterBag->get('project_export_filename').'_receipt_reminder_'.$object->getSluggedReceiptNumber().'.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function sendReminderAction(Request $request): RedirectResponse
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        if (StudentPaymentEnum::BANK_ACCOUNT_NUMBER === $object->getMainSubject()->getPayment()) {
            throw $this->createNotFoundException(sprintf('invalid payment type for object with id: %s', $object->getId()));
        }
        $pdf = $this->rbp->build($object);
        $result = $this->ns->sendReceiptReminderPdfNotification($object, $pdf);
        if (0 === $result) {
            $this->addFlash('danger', 'S\'ha produït un error durant l\'enviament del recordatori de pagament del rebut núm. '.$object->getReceiptNumber().'. La persona '.$object->getMainEmailName().' no ha rebut cap missatge a la seva bústia.');
        } else {
            $this->addFlash('success', 'S\'ha enviat el recordatori de pagament del rebut núm. '.$object->getReceiptNumber().' amb PDF a la bústia '.$object->getMainEmail());
        }

        return $this->redirectToList();
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function pdfAction(Request $request, ParameterBagInterface $parameterBag): Response
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $pdf = $this->rbp->build($object);

        return new Response($pdf->Output($parameterBag->get('project_export_filename').'_receipt_'.$object->getSluggedReceiptNumber().'.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function sendAction(Request $request): RedirectResponse
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $object
            ->setIsSended(true)
            ->setSendDate(new \DateTimeImmutable())
        ;
        $this->mr->getManager()->flush();
        $pdf = $this->rbp->build($object);
        $result = $this->ns->sendReceiptPdfNotification($object, $pdf);
        if (0 === $result) {
            $this->addFlash('danger', 'S\'ha produït un error durant l\'enviament del rebut núm. '.$object->getReceiptNumber().'. La persona '.$object->getMainEmailName().' no ha rebut cap missatge a la seva bústia.');
        } else {
            $this->addFlash('success', 'S\'ha enviat el rebut núm. '.$object->getReceiptNumber().' amb PDF a la bústia '.$object->getMainEmail());
        }

        return $this->redirectToList();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function generateDirectDebitAction(Request $request): Response
    {
        /** @var Receipt $object */
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);
        $this->checkParentChildAssociation($request, $object);
        $this->admin->checkAccess('show', $object);
        $paymentUniqueId = uniqid('', true);
        $xml = $this->xsbs->buildDirectDebitSingleReceiptXml($paymentUniqueId, new \DateTime('now + 3 days'), $object);
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
        $fileNamePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'SEPA_receipt_'.$now->format('Y-m-d_H-i').'.xml';
        $fileSystem->touch($fileNamePath);
        $fileSystem->dumpFile($fileNamePath, $xml);
        $response = new BinaryFileResponse($fileNamePath, Response::HTTP_OK, ['Content-type' => 'application/xml']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function batchActionGeneratereminderspdf(ProxyQueryInterface $query, ParameterBagInterface $parameterBag): Response
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $query->execute();
        try {
            $pdf = $this->rrbp->buildBatchReminder();
            /** @var Receipt $selectedModel */
            foreach ($selectedModels as $selectedModel) {
                if (StudentPaymentEnum::BANK_ACCOUNT_NUMBER !== $selectedModel->getMainSubject()->getPayment() && !$selectedModel->getStudent()?->getIsPaymentExempt()) {
                    // add page
                    $pdf->AddPage('L', 'A5', true, true);
                    $this->rrbp->buildReceiptRemainderPageForItem($pdf, $selectedModel);
                }
            }

            return new Response($pdf->Output($parameterBag->get('project_export_filename').'_receipt_reminders.pdf'), Response::HTTP_OK, ['Content-type' => 'application/pdf']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'S\'ha produït un error al generar l\'arxiu de recordatoris de pagaments de rebut amb format PDF. Revisa els rebuts seleccionats.');
            $this->addFlash('error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters(),
                ])
            );
        }
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function batchActionGeneratesepaxmls(ProxyQueryInterface $query, MessageBusInterface $bus): Response
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $query->execute();
        try {
            $paymentUniqueId = uniqid('', true);
            $xmlsArray = [];
            $banksCreditorSepa = $this->bcsr->getEnabledSortedByName();
            /** @var BankCreditorSepa $bankCreditorSepa */
            foreach ($banksCreditorSepa as $bankCreditorSepa) {
                $xmlsArray[] = $this->xsbs->buildDirectDebitReceiptsXmlForBankCreditorSepa($paymentUniqueId, new \DateTime('now + 3 days'), $selectedModels, $bankCreditorSepa);
            }
            $selectedModelIdsArray = [];
            /** @var Receipt $selectedModel */
            foreach ($selectedModels as $selectedModel) {
                if ($selectedModel->isReadyToGenerateSepa() && !$selectedModel->getStudent()?->getIsPaymentExempt()) {
                    $selectedModelIdsArray[] = $selectedModel->getId();
                    $selectedModel
                        ->setIsSepaXmlGenerated(true)
                        ->setSepaXmlGeneratedDate(new \DateTimeImmutable())
                    ;
                }
            }
            $this->mr->getManager()->flush();
            $bus->dispatch(new NewReceiptGroupCreatedMessage($selectedModelIdsArray));
            $now = new \DateTimeImmutable();
            $fileName = 'SEPA_receipts_'.$now->format('Y-m-d_H-i').'.zip';
            $fileNamePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
            $zipFile = new ZipFile();
            $index = 0;
            /** @var BankCreditorSepa $bankCreditorSepa */
            foreach ($banksCreditorSepa as $bankCreditorSepa) {
                if ($xmlsArray[$index]['receipts_generated_amount'] > 0) {
                    $zipFile->addFromString(sprintf('SEPA_%s.xml', StringHelper::sanitizeString($bankCreditorSepa->getName())), $xmlsArray[$index]['xml']);
                }
                ++$index;
            }
            $zipFile->saveAsFile($fileNamePath)->close();
            $response = new BinaryFileResponse($fileNamePath, Response::HTTP_OK, ['Content-type' => 'application/zip']);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'S\'ha produït un error al generar l\'arxiu SEPA amb format XML. Revisa els rebuts seleccionats.');
            $this->addFlash('error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters(),
                ])
            );
        }
    }

    #[IsGranted(UserRolesEnum::ROLE_ADMIN)]
    public function batchActionMarkaspayed(ProxyQueryInterface $query): RedirectResponse
    {
        $this->admin->checkAccess('edit');
        $selectedModels = $query->execute();
        try {
            /** @var Receipt $selectedModel */
            foreach ($selectedModels as $selectedModel) {
                $selectedModel
                    ->setIsPayed(true)
                    ->setPaymentDate(new \DateTimeImmutable())
                ;
            }
            $modelManager = $this->admin->getModelManager();
            $modelManager->update($selectedModel);
            $this->addFlash('success', 'S\'han marcat '.count($selectedModels).' rebuts com a pagats correctament.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'S\'ha produït un error al generar marcar els rebuts com a pagats. Revisa els rebuts seleccionats.');
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToList();
    }
}
