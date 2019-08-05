<?php

use Unilend\Entity\{ProductAttributeType, RepaymentType, UnderlyingContractAttributeType, Zones};

class productController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);

        $this->menu_admin = 'emprunteurs';
        $this->translator = $this->get('translator');
    }

    public function _default()
    {
        /** @var \product $product */
        $product           = $this->loadData('product');
        $this->productList = $product->select();
    }

    public function _edit()
    {
        /** @var product product */
        $this->product = $this->loadData('product');

        if (false === isset($this->params[0]) || false === $this->product->get($this->params[0])) {
            header('Location: /product');
        }

        /** @var product_underlying_contract $productContract */
        $productContract     = $this->loadData('product_underlying_contract');
        $this->contracts     = $productContract->getUnderlyingContractsByProduct($this->product->id_product);
        $this->repaymentType = $this->get('doctrine.orm.entity_manager')->getRepository(RepaymentType::class)->find($this->product->id_repayment_type);

        // max / min duration
        /** @var \Unilend\Service\Product\ProductManager $productManager */
        $productManager        = $this->get('unilend.service_product.product_manager');
        $this->duration['min'] = $productManager->getAttributesByType($this->product, ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH);
        $this->duration['max'] = $productManager->getAttributesByType($this->product, ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        // motivation
        $borrowerMotives = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE);
        /** @var \borrowing_motive $motive */
        $motive                = $this->loadData('borrowing_motive');
        $this->borrowerMotives = [];
        if (false === empty($borrowerMotives)) {
            foreach ($borrowerMotives as $borrowerMotive) {
                $motive->get($borrowerMotive);
                $this->borrowerMotives[] = $motive->motive;
            }
        }

        // excluded motivation
        $borrowerExcludedMotives = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_EXCLUDED_BORROWING_MOTIVE);
        /** @var \borrowing_motive $motive */
        $motive                = $this->loadData('borrowing_motive');
        $this->borrowerExcludedMotives = [];
        if (false === empty($borrowerExcludedMotives)) {
            foreach ($borrowerExcludedMotives as $borrowerExcludedMotive) {
                $motive->get($borrowerExcludedMotive);
                $this->borrowerExcludedMotives[] = $motive->motive;
            }
        }

        $this->creationDaysMin       = $productManager->getAttributesByType($this->product, ProductAttributeType::MIN_CREATION_DAYS);
        $this->rcs                   = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);
        $this->nafcodes              = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);
        $this->lenderId              = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_CLIENT_ID);
        $this->lenderType            = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_CLIENT_TYPE);
        $this->checkExcludedLocation = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_EXCLUDED_HEADQUARTERS_LOCATION);
        $this->maxXerfiScore         = $productManager->getAttributesByType($this->product, ProductAttributeType::MAX_XERFI_SCORE);
        $this->noBlendDays           = $productManager->getAttributesByType($this->product, ProductAttributeType::NO_IN_PROGRESS_BLEND_PROJECT_DAYS);
        $this->noIncidentBlendDays   = $productManager->getAttributesByType($this->product, ProductAttributeType::NO_INCIDENT_BLEND_PROJECT_DAYS);
        $this->noIncidentUnilendDays = $productManager->getAttributesByType($this->product, ProductAttributeType::NO_INCIDENT_UNILEND_PROJECT_DAYS);
        $this->minPreScore           = $productManager->getAttributesByType($this->product, ProductAttributeType::MIN_PRE_SCORE);
        $this->maxPreScore           = $productManager->getAttributesByType($this->product, ProductAttributeType::MAX_PRE_SCORE);
        $this->legalFormCode         = $productManager->getAttributesByType($this->product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_LEGAL_FORM_CODE);
    }

    public function _contract_details()
    {
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');

        if (false === isset($this->params[0]) || false === $this->contract->get($this->params[0])) {
            header('Location: /product');
        }

        /** @var \Unilend\Service\Product\Contract\ContractManager $contractManager */
        $contractManager = $this->get('unilend.service_product_contract.contract_manager');

        $this->lenderType         = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::ELIGIBLE_CLIENT_TYPE);
        $this->loanAmountMax      = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        $this->loanQtyMax         = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::TOTAL_QUANTITY_LIMITATION);
        $this->loanDurationMax    = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH);
        $this->creationDaysMin    = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::MIN_CREATION_DAYS);
        $this->rcs                = $contractManager->getAttributesByType($this->contract, UnderlyingContractAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);
    }
}
