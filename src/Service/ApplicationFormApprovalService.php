<?php

namespace App\Service;

use App\Entity\ApplicationForm;
use App\Repository\ApplicationFormRepository;
use App\Repository\InstitutionsRepository;

readonly class ApplicationFormApprovalService
{
    public function __construct(
        private ApplicationFormRepository      $forms,
        private InstitutionsRepository         $institutions,
        private ApplicationFormDocumentService $docService,
    ) {}

    public function approve(ApplicationForm $form): void
    {
        $form->setStatus('Принята');

        if ($inst = $this->institutions->findOneBy(['title' => $form->getInstitute()])) {
            $inst->setCountAccepts($inst->getCountAccepts() + 1);
            $this->institutions->save($inst, true);
        }

        $this->docService->process($form);
        $this->forms->save($form, true);
    }

    public function reject(ApplicationForm $form): void
    {
        $form->setStatus('Отклонена');
        $this->forms->save($form, true);
    }
}
