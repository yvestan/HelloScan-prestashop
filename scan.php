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

// debug ?
define('HELLOSCAN_DEBUG', false);

// get params from helloscan request
class HelloScan_RequestParams {

    // code from scan result
    public $code = null;

    // action from app
    public $action = null;

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
        if(!empty($_GET['code']) && is_numeric($_GET['code'])) {
            return $this->code = (int)$_GET['code'];
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
            return $this->action = htmlspecialchars($_GET['action']); 
        }
        return false;
    }

    // }}}

    // {{{ getAuthKey()

    /** get authentification key
     *
     */
    public function getAuthKey() {
        if(!empty($_GET['authkey'])) {
            return $this->authkey = htmlspecialchars($_GET['authkey']); 
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
class HelloScan_Check {

    // request params
    protected $params = null;
       
    // product fields rturn format json
    private $return_fields = array(
        'name',
        'price',
        'quantity',
        'id_product',
        'ean13',
        'reference',
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
    }

    // }}}


    // {{{ checkProductByCode()

    /** check if code is associate with product
     *
     */
    public function checkProductByCode() {

        // find product by EAN
        $sql = 'SELECT p.`id_product` FROM '._DB_PREFIX_.'product p
                WHERE p.`ean13`='.pSQL($this->params->getCode()).' ';
        $id_product = DB::getInstance()->getValue($sql);

        // debug
        $this->setDebug('SQL check', $sql);

        // get product
        if(!empty($id_product)) {
            $product = new Product($id_product);
            if (!Validate::isLoadedObject($product) || !$product->active) {
                return false;
            } else {
                $product->id_product = $id_product;
                return $product;
            }
        } else {
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
            foreach($product as $k=>$v) {
                if(in_array($k, $this->return_fields)) {
                    $product_tabs[$k] = $v;
                }
            }
            return array(
                'status' => 200,
                'result' => 'Product informations',
                'data' => $product_tabs,
            );
        } else {
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
            $sql = 'UPDATE '._DB_PREFIX_.'product
                    SET `quantity` = `quantity`+'.intval(1).'
                    WHERE `id_product` = '.$product->id_product;
            $this->setDebug('add SQL', $sql);
            if(Db::getInstance()->Execute($sql)) {
                return array(
                    'status' => '200',
                    'result' => ' Quantity updated: add 1'
                );
            } else {
                return array(
                    'status' => '500',
                    'result' => 'Error during quantity update: add 1'
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
        if($product = (array)$this->checkProductByCode()) {
            $product['cart_quantity'] = 1;
            $this->setDebug('update Quantity', $product['cart_quantity']);
            if(Product::updateQuantity($product)) {
                return array(
                    'status' => '200',
                    'result' => 'Quantity updated: remove 1'
                );
            } else {
                return array(
                    'status' => '500',
                    'result' => 'Error during quantity update: remove 1'
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

    // {{{ setDebug()

    /** debug
     *
     * @param string $key Key
     * @param string $value Value debug
     */
    public function setDebug($key,$value) {
        if($this->debug) {
            echo $key.' : '.$value;
        }
    }

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
