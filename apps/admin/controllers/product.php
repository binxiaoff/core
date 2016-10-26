<?php
class productController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('emprunteurs');
        $this->menu_admin = 'emprunteurs';
        $this->translator = $this->get('translator');
    }

    public function _default()
    {
        /** @var \product $product */
        $product = $this->loadData('product');
        $this->productList = $product->select();
    }

    public function _edit()
    {
        /** @var product product */
        $this->product = $this->loadData('product');

        if (false === isset($this->params[0]) || false === $this->product->get($this->params[0])) {
            header('Location: /product');
        }

        // repayment type
        /** @var repayment_type repaymentType */
        $this->repaymentType = $this->loadData('repayment_type');
        /** @var product_underlying_contract $productContract */
        $productContract = $this->loadData('product_underlying_contract');
        $this->contracts = $productContract->getUnderlyingContractsByProduct($this->product->id_product);
        $this->repaymentType->get($this->product->id_repayment_type);

        // max / min duration
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager $productManager */
        $productManager = $this->get('unilend.service_product.product_manager');
        $this->duration['min'] = $productManager->getAttributesByType($this->product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH);
        $this->duration['max'] = $productManager->getAttributesByType($this->product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH);

        // motivation
        $borrowerMotives = $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_BORROWING_MOTIVE);
        /** @var borrowing_motive $need */
        $motive = $this->loadData('borrowing_motive');
        $this->borrowerMotives = [];
        if (false === empty($borrowerMotives)) {
            foreach ($borrowerMotives as $borrowerMotive) {
                $motive->get($borrowerMotive);
                $this->borrowerMotives[] = $motive->motive;
            }
        }

        // creation days
        $this->creationDaysMin = $productManager->getAttributesByType($this->product, \product_attribute_type::MIN_CREATION_DAYS);

        // RCS
        $this->rcs = $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS);

        // code NAF
        // motivation
        $this->nafcodes = $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);
    }

    public function _contract_details()
    {
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');

        if (false === isset($this->params[0]) || false === $this->contract->get($this->params[0])) {
            header('Location: /product');
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager $contractManager */
        $contractManager = $this->get('unilend.service_product_contract.contract_manager');

        // lender type
        $this->lenderType = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);

        // max loan amount
        $this->loanAmountMax = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

        // max loan quantity
        $this->loanQtyMax = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::TOTAL_QUANTITY_LIMITATION);

        // max loan duration
        $this->loanDurationMax = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH);

        // Autobid eligibility
        $this->eligibilityAutobid = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::ELIGIBLE_AUTOBID);

        // company min creation days
        $this->creationDaysMin = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::MIN_CREATION_DAYS);

        // RCS
        $this->rcs = $contractManager->getAttributesByType($this->contract, underlying_contract_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS);
    }
}
