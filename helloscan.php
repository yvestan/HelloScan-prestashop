<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
*
* HelloScan for PrestaShop
*
* @package HelloScan_PrestaShop
* @author Yves Tannier [grafactory.net]
* @copyright 2011 Yves Tannier
* @link http://helloscan.mobi
* @version 0.1
* @license MIT Licence
*/

class HelloScan extends Module
{

    private $_html = '';

    function __construct()
    {
        $this->name = 'helloscan';
        parent::__construct();
        
        $this->tab = 'Stocks';
        $this->version = '0.1.0';
        $this->displayName = $this->l('HelloScan');
        $this->description = $this->l('Gestion des stocks via des codes barres et l\'application HelloScan');

    }

    public function getContent()
    {
        if (Tools::isSubmit('submit')) {
            Configuration::updateValue($this->name.'_authkey', Tools::getValue($this->name.'_authkey'));
            if(!empty($_POST[$this->name.'_active_fields']) && is_array($_POST[$this->name.'_active_fields'])) {
                Configuration::updateValue($this->name.'_active_fields', serialize($_POST[$this->name.'_active_fields']));
            }
        }

        $this->_displayForm();

        return $this->_html;
    }

    private function _displayForm()
    {
            $available_fields = array(
				'id' => array('label' => $this->l('ID')),
				'active' => array('label' => $this->l('Active (0/1)')),
				'name' => array('label' => $this->l('Name *')),
				'location' => array('label' => $this->l('Location')),
				//'category' => array('label' => $this->l('Categories (x,y,z...)')),
				'price' => array('label' => $this->l('Price')),
				/*'price_tex' => array('label' => $this->l('Price tax excl.')),
				'price_tin' => array('label' => $this->l('Price tax incl.')),*/
				//'id_tax_rules_group' => array('label' => $this->l('Tax rules id')),
				'wholesale_price' => array('label' => $this->l('Wholesale price')),
				'on_sale' => array('label' => $this->l('On sale (0/1)')),
				'reference' => array('label' => $this->l('Reference #')),
				//'supplier_reference' => array('label' => $this->l('Supplier reference #')),
				//'supplier' => array('label' => $this->l('Supplier')),
				//'manufacturer' => array('label' => $this->l('Manufacturer')),
				'ean13' => array('label' => $this->l('EAN13')),
				'upc' => array('label' => $this->l('UPC')),
				'ecotax' => array('label' => $this->l('Ecotax')),
				'quantity' => array('label' => $this->l('Quantity')),
				'description_short' => array('label' => $this->l('Short description')),
				//'description' => array('label' => $this->l('Description')),
				//'tags' => array('label' => $this->l('Tags (x,y,z...)')),
				//'meta_title' => array('label' => $this->l('Meta-title')),
				//'meta_keywords' => array('label' => $this->l('Meta-keywords')),
				//'meta_description' => array('label' => $this->l('Meta-description')),
				//'link_rewrite' => array('label' => $this->l('URL rewritten')),
				//'available_now' => array('label' => $this->l('Text when in-stock')),
				//'available_later' => array('label' => $this->l('Text if back-order allowed')),
				//'available_for_order' => array('label' => $this->l('Available for order')),
				'date_add' => array('label' => $this->l('Date add product')),
				//'show_price' => array('label' => $this->l('Show price')),
				//'feature' => array('label' => $this->l('Feature')),
				'online_only' => array('label' => $this->l('Only available online')),
				//'condition' => array('label' => $this->l('Condition'))
				'weight' => array('label' => $this->l('Weight')),
				'reduction_price' => array('label' => $this->l('Discount amount')),
				'reduction_percent' => array('label' => $this->l('Discount percent')),
				'reduction_from' => array('label' => $this->l('Discount from (yyyy-mm-dd)')),
				'reduction_to' => array('label' => $this->l('Discount to (yyyy-mm-dd)')),
            );

        $active_fields = unserialize(Configuration::get($this->name.'_active_fields', NULL));

        foreach($available_fields as $k=>$v) {
            $html_checkbox[$k] = '<input type="checkbox" id="'.$this->name.'_active_fields['.$k.']" name="'.$this->name.'_active_fields['.$k.']"';
            if(array_key_exists($k, $active_fields)) {
                $html_checkbox[$k] .= ' checked="checked"';
            }
            $html_checkbox[$k] .= ' />&nbsp;'.$v['label'];
        }

        $actual_authkey = Configuration::get($this->name.'_authkey',NULL);
        $this->_html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        if (Tools::isSubmit('submit')) {
            echo '<div style="color:green;font-weight:bold;" class="margin-form">Configuration enregistrée</div>';
        }
        $this->_html .='
                <label>'.$this->l('Clé HelloScan').'</label>
                <div class="margin-form">
                    <input type="text" name="'.$this->name.'_authkey" value="'.$actual_authkey.'" /> <em>Clé d\'authentification de l\'App HelloScan</em>
                </div>
                <div class="margin-form">
                    <h4 style="color:black;">Les champs à activer</h4>'.join('<br />', $html_checkbox).'</h4>
                </div>
                <div class="margin-form">
                    <input type="submit" name="submit" value="'.$this->l('OK').'" class="button" />
                </div>
        </form>';
    }

}
