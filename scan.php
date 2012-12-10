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
* @license MIT Licence
*/

// config & init PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');

// get params from helloscan request
class HelloScan_RequestParams {

    // code from scan result
    public $code = null;

    // action from app
    public $action = null;

    // qty from app
    public $qty = 1;

    // action from app
    public $authkey = null;

    // possible actions
    protected $actions = array(
        'get',
        'add',
        'remove',
    );

    // {{{ getCode()

    /** get code : ean13, reference or id_product
     *
     */
    public function getCode() {
        HelloScan_Utils::setDebug('getCode[before]', $_GET['code']);
        if(!empty($_GET['code'])) {
            HelloScan_Utils::setDebug('getCode[after]', trim(htmlspecialchars(strip_tags($_GET['code']))));
            return $this->code = trim(htmlspecialchars(strip_tags($_GET['code'])));
        }
        return false;
    }

    // }}}

    // {{{ codeExist()

    /** check product code
     *
     */
    public function codeExist() {
        if(!$this->getCode()) {
            return false;
        }
        return true;
    }

    // }}}

    // {{{ getAction()

    /** get action (check, add, remove...)
     *
     */
    public function getAction() {
        if(!empty($_GET['action']) && in_array($_GET['action'], $this->actions)) {
            return $this->action = trim(htmlspecialchars($_GET['action'])); 
        }
        return false;
    }

    // }}}

    // {{{ getQty()

    /** get quantity to change stock
     *
     */
    public function getQty() {
        if(!empty($_GET['qty']) && is_numeric($_GET['qty'])) {
            return $this->qty = trim(htmlspecialchars($_GET['qty'])); 
        } else {
            return $this->qty;
        }
    }

    // }}}


    // {{{ getAuthKey()

    /** get authentification key
     *
     */
    public function getAuthKey() {
        if(!empty($_GET['authkey'])) {
            return $this->authkey = trim(htmlspecialchars($_GET['authkey'])); 
        }
        return false;
    }

    // }}}

}

// check autorisation key
class HelloScan_AuthKey {

    // request params
    protected $params = null;

    // {{{ __construct()

    /** constructeur
     *
     * @param object $params request parameters
     */
    public function __construct($params) {
        $this->params = $params;
    }

    // }}}

    // {{{ check()

    /** auth key / compare with saved authkey TODO
     *
     */
    public function check() {
        if($this->params->getAuthKey() 
            && Configuration::get('helloscan_authkey',NULL)==$this->params->getAuthKey()) {
            $this->authkey = $this->params->getAuthKey();
            return true;
        }
        return false;
    }

    // }}}

}
   
// check product code and perform actions
class HelloScan_Check extends Module {

    // request params
    protected $params = null;

    // field to find product
    public $search_field = 'ean13';
       
    // product fields rturn format json
    private $return_fields = array(
        'name' => 'on',
        'price' => 'on',
        'quantity' => 'on',
        'id_product' => 'on',
        'ean13' => 'on',
        'reference' => 'on',
        'location' => 'on',
    );

    // debug mode
    private $debug = HELLOSCAN_DEBUG;

    // {{{ __construct()

    /** constructeur
     *
     * @param object $params request parameters
     */
    public function __construct($params) {
        $this->params = $params;
        // debug mode
        if(!empty($_GET['debug_mode'])) {
            $this->debug = true;
        }
    }

    // }}}

    // {{{ getAttribute()

    /**
     * @return array of Groups/Attribute
     * param integer $id_product_attribute
     * param integer $id_lang
     */
    public function getAttribute($id_product_attribute, $id_lang = null) {         
        $sql = '
        SELECT * 
        FROM `'._DB_PREFIX_.'product_attribute`  pa
        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pa.`id_product`=pl.`id_product` 
        WHERE pa.`id_product_attribute`='.intval($id_product_attribute);

        // langue
        if(!empty($id_lang)) {
            $sql .= ' AND `id_lang` = '.intval($id_lang);
        }

        return DB::getInstance()->ExecuteS($sql);

    }

    // }}}

    // {{{ getAttributeDescription()

    /**
     * @return array of Groups/Attribute available for a designed product
     * param integer $id_product_attribute
     * param integer $id_lang
     */
    public function getAttributeDescription($id_product_attribute, $id_lang = null) {         

        $sql = '
            SELECT * FROM
            `'._DB_PREFIX_.'product_attribute_combination`  pac
            JOIN  `'._DB_PREFIX_.'attribute_lang` al ON pac.`id_attribute`=al.`id_attribute`
            WHERE id_product_attribute='.intval($id_product_attribute);

        // langue
        if(!empty($id_lang)) {
            $sql .= ' AND al.`id_lang` = '.intval($id_lang).' ';
        }
        return DB::getInstance()->ExecuteS($sql);
    }

    // }}}

    // {{{ checkProductByCode()

    /** check if code is associate with product
     *
     */
    public function checkProductByCode() {

        // langue get from config
        $id_lang = Configuration::get('helloscan_id_lang');

        // find product by EAN
        $sql = 'SELECT p.`id_product` FROM '._DB_PREFIX_.'product p
                WHERE p.`'.$this->search_field.'`=\''.pSQL($this->params->getCode()).'\' ';

        $id_product = DB::getInstance()->getValue($sql);

        // find product_attribute
        if(empty($id_product)) {
            $sql = 'SELECT pa.`id_product_attribute`, pa.`id_product` FROM '._DB_PREFIX_.'product_attribute pa
                    WHERE pa.`'.$this->search_field.'`=\''.pSQL($this->params->getCode()).'\' ';

            $result = DB::getInstance()->getRow($sql);

            $id_product = $result['id_product'];
            $id_product_attribute = $result['id_product_attribute'];

            HelloScan_Utils::setDebug('checkProductByCode[SQL_attribute]', $sql);
        }

        HelloScan_Utils::setDebug('checkProductByCode[SQL]', $sql);

        // get product
        if(!empty($id_product)) {
            $product = new Product($id_product,false,$id_lang);
           //if (!Validate::isLoadedObject($product) || !$product->active) {
            if (!Validate::isLoadedObject($product)) {
                HelloScan_Utils::setDebug('checkProductByCode[Validate::isLoadedObject]', 'Unable to validate');
                return false;
            } else {
                $product->id_product = $id_product;
                if(!empty($id_product_attribute)) {
                    $product_attribute = $this->getAttribute($id_product_attribute,$id_lang);
                    $product_attribute['id_product'] = $id_product;
                    $product_attribute['id_product_attribute'] = $id_product;
                    // cast
                    $product = (object)$product_attribute[0];
                }
                return $product;
            }
        } else {
            HelloScan_Utils::setDebug('checkProductByCode[id_product]', 'empty id_product after SQL query');
            return false;
        }

    }

    // }}}

    // {{{ get()

    /** get product infos from code
     *
     * @return array
     */
    public function get() {

        if($product = $this->checkProductByCode()) {

            HelloScan_Utils::setDebug('get[checkProductByCode]', 'Product find');

            // get active fields from module conf
            $active_fields = unserialize(Configuration::get('helloscan_active_fields',array()));
            if(!empty($active_fields)) {
                $this->return_fields = $active_fields;
            }
            /*print_r($product);
            exit;*/

            foreach($product as $k=>$v) {

                // add attribute description
                if(isset($product->id_product_attribute)) {
                    // langue get from config
                    $id_lang = Configuration::get('helloscan_id_lang');
                    // with langue
                    $attributes = $this->getAttributeDescription($product->id_product_attribute,$id_lang);
                    // take the first lang on array
                    foreach($attributes as $a) {
                        if(empty($product_tabs['attribute_'.$a['id_attribute']])) {
                            $product_tabs['attribute_'.$a['id_attribute']] = $a['name'];
                        }
                    }
                }

                if(array_key_exists($k, $this->return_fields)) {

                    // add sale price
                    if($k=='price') {
                        if(method_exists('Tax','getProductTaxRate')) {
                            $tax_rate = Tax::getProductTaxRate($product->id_product);
                            if(!empty($tax_rate)) {
                                $product_tabs['sale_price'] = round($product->price+($product->price*$tax_rate/100));
                            }
                        }
                    }

                    // hack for lang product name
                    if($k=='name' && is_array($v)) {
                        foreach($v as $lng_product) {
                            if(!empty($lng_product)) {
                                $v = $lng_product;
                                break;
                            }
                        }
                    } elseif(is_array($v)) {
                        $v = join('<br /><br />', $v);
                    }

                    // empty field
                    if(empty($v)) {
                        $v = 'unknown';
                    }

                    $product_tabs[$k] = $v;
                }
            }
            return array(
                'status' => 200,
                'result' => 'Product informations',
                'data' => $product_tabs,
            );
        } else {
           HelloScan_Utils::setDebug('get[checkProductByCode]', 'Unable to find product');
           return array(
                'status' => 404,
                'result' => 'No product found',
           );
        }
    }

    // }}}

    // {{{ add()

    /** add 1 product from stock
     *
     * @return array
     */
    public function add() {
        if($product = $this->checkProductByCode()) {
            $product->id = $product->id_product;
            // rename variable why ?
            $product->product_id = $product->id_product;
            if(!empty($product->id_product_attribute)) {
                $product->product_attribute_id = $product->id_product_attribute;
            }
            if(Product::reinjectQuantities($product, intval($this->params->getQty()))) {
                return array(
                    'status' => '200',
                    'result' => ' Quantity updated: add '.$this->params->getQty()
                );
            } else {
                return array(
                    'status' => '500',
                    'result' => 'Error during quantity update: add '.$this->params->getQty()
                );
            }
        } else {
           return array(
                'status' => 404,
                'result' => 'No product found to add quantity',
           );
        }
    }

    // }}}

    // {{{ remove()

    /** remove 1 product from stock
     *
     * @return array
     */
    public function remove() {
        if($product = $this->checkProductByCode()) {
            if(!empty($product->id_product_attribute)) {
                $id_product_attribute = $product->id_product_attribute;
            }
            $product = (array)$product;
            $product['cart_quantity'] = $this->params->getQty();
            if(!empty($id_product_attribute)) {
                $product['id_product_attribute'] = $id_product_attribute;
            }
            if(Product::updateQuantity($product)) {
                return array(
                    'status' => '200',
                    'result' => 'Quantity updated: remove '.$this->params->getQty()
                );
            } else {
                return array(
                    'status' => '500',
                    'result' => 'Error during quantity update: remove '.$this->params->getQty()
                );
            }
        } else {
           return array(
                'status' => 404,
                'result' => 'No product found to remove quantity',
           );
        }
    }

    // }}}

    // {{{ excute()

    /** perform action and get result array
     *
     * @return array
     */
    public function execute() {
        return $this->{$this->params->getAction()}();
    }

    // }}}


}
    

// response format and send
class HelloScan_Utils {

    // {{{ setDebug()

    /** debug
     *
     * @param string $key Key
     * @param string $value Value debug
     */
    static public function setDebug($key,$value) {
        // debug mode
        if(!empty($_GET['debug_mode'])) {
            echo '&raquo; '.$key.' : '.$value.'<br /><br />';
        }
    }

    // }}}

}

// response format and send
class HelloScan_ResponseHandler {

    // {{{ sendResponse()

    /** response
     *
     */
    public function sendResponse($response,$format='json') {
        if($format=='json') {
            //header('Content-Type: application/json'); 
            echo json_encode($response);
        }
        exit;
    }

    // }}}

}

// user parameters
$HS_requestParams = new HelloScan_RequestParams();

// response handler
$HS_responseHandler = new HelloScan_ResponseHandler();

// check key
$HS_authKey = new HelloScan_AuthKey($HS_requestParams);

if(!$HS_authKey->check()) {
    // send response and exit
    $HS_responseHandler->sendResponse(array(
        'status' => '401',
        'result' => 'Bad authorisation key'
    ));
}

// check product code
if(!$HS_requestParams->codeExist()) {
    // send response and exit
    $HS_responseHandler->sendResponse(array(
        'status' => '404',
        'result' => 'Product code unvalaible'
    ));
}

// helloscan
$HS_check = new HelloScan_Check($HS_requestParams);

// method = action
if(method_exists($HS_check,$HS_requestParams->getAction())) {
    // perform action
    $HS_actionResult = $HS_check->execute();
    // send result and exit
    $HS_responseHandler->sendResponse($HS_actionResult);
} else {
    // no action =  reponse and exit
    $HS_responseHandler->sendResponse(array(
        'status' => '404',
        'result' => 'Action unvailable or not specified'
    ));
}   
