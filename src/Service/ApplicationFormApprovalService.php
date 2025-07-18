<?php

namespace App\Service;

use App\Entity\People;
use App\Repository\InstitutionsRepository;
use App\Repository\PeopleRepository;

readonly class ApplicationFormApprovalService
{
    public function __construct(
        private PeopleRepository      $forms,
        private InstitutionsRepository         $institutions,
        private ApplicationFormDocumentService $docService,
    ) {}

//    public function approve(People $form): void
//    {
//        $form->setStatus('Принята');
//
//        if ($inst = $this->institutions->findOneBy(['title' => $form->getInstitute()])) {
//            $inst->setCountAccepts($inst->getCountAccepts() + 1);
//            $this->institutions->save($inst, true);
//        }
//
//        $this->docService->process($form);
//        $this->forms->save($form, true);
//    }
//
//    public function reject(People $form): void
//    {
//        $form->setStatus('Отклонена');
//        $this->forms->save($form, true);
//    }
}
