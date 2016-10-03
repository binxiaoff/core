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

        /** @var repayment_type repaymentType */
        $this->repaymentType = $this->loadData('repayment_type');
        /** @var product_underlying_contract $productContract */
        $productContract = $this->loadData('product_underlying_contract');

        $this->contracts = $productContract->getUnderlyingContractsByProduct($this->product->id_product);
        $this->repaymentType->get($this->product->id_repayment_type);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager $productManager */
        $productManager = $this->get('unilend.service_product.product_manager');
        $this->duration['min'] = $productManager->getAttributesByType($this->product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH);
        $this->duration['max'] = $productManager->getAttributesByType($this->product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH);

        $lenderNationalities = $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY);
        /** @var nationalites_v2 $productContract */
        $nationality = $this->loadData('nationalites_v2');
        $this->lenderNationalities = [];
        if (false === empty($lenderNationalities)) {
            foreach ($lenderNationalities as $lenderNationality) {
                $nationality->get($lenderNationality);
                $this->lenderNationalities[] = $nationality->fr_f;
            }
        }

        $borrowerCountries =  $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_COUNTRY);
        /** @var pays_v2 $productContract */
        $pays = $this->loadData('pays_v2');
        $this->borrowerCountries = [];
        if (false === empty($borrowerCountries)) {
            foreach ($borrowerCountries as $borrowerCountry) {
                $pays->get($borrowerCountry);
                $this->borrowerCountries[] = $pays->fr;
            }
        }

        $borrowerNeeds = $productManager->getAttributesByType($this->product, \product_attribute_type::ELIGIBLE_NEED);
        /** @var project_need $need */
        $need = $this->loadData('project_need');
        $this->borrowerNeeds = [];
        if (false === empty($borrowerNeeds)) {
            foreach ($borrowerNeeds as $borrowerNeed) {
                $need->get($borrowerNeed);
                $this->borrowerNeeds[] = $need->label;
            }
        }
    }
}
