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
        }

        $this->_displayForm();

        return $this->_html;
    }

    private function _displayForm()
    {
        $actual_authkey = Configuration::get($this->name.'_authkey',NULL);
        $this->_html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
                <label>'.$this->l('Clé d\'authentification de l\'App HelloScan').'</label>
                <div class="margin-form">
                    <input type="text" name="'.$this->name.'_authkey" value="'.$actual_authkey.'" />
                </div>
                <input type="submit" name="submit" value="'.$this->l('OK').'" class="button" />
        </form>';
    }

}
