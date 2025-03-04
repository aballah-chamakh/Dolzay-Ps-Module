<?php

class DolzayHappyCustomerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Assign variables to the template
        $this->context->smarty->assign([
            'module_base_link' =>  $this->context->link->getBaseLink() ,
            'domain_name' => Tools::getShopDomain()
        ]);


        // Set the template
        $this->setTemplate('module:dolzay/views/templates/front/happy_customer.html.tpl');
    }
}