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

// check authkey
if(empty($_GET['authkey']) || Configuration::get('helloscan_authkey',NULL)!=$_GET['authkey']) {
    echo 'Incorrect authkey';
    exit;
} else {
    $hs_authkey = Configuration::get('helloscan_authkey',NULL);
}

// url prestashop
if(method_exists('Tools','getHttpHost')) {
    $hs_url_prestashop = Tools::getHttpHost(true);
} elseif(method_exists('Tools','getShopDomain')) {
    $hs_url_prestashop = Tools::getShopDomain('true');
    $hs_url_prestashop_ssl = Tools::getShopDomainSsl('true');
} else {
    $hs_url_prestashop = 'http://'.$_SERVER['HTTP_HOST'];
}

$hs_url_module = $hs_url_prestashop.__PS_BASE_URI__.'modules/helloscan/';

header ('Content-Type:text/xml'); 
?> 
<helloscan>
    <button>
        <label value="Scanne"></label>
        <url value="<?php echo $hs_url_module; ?>scan.php?authkey=<?php echo $hs_authkey; ?>&amp;code=&lt;id&gt;&amp;action=get"></url>
        <action value="true"></action>
        <color value="buttonBlue"></color>
    </button>
    <button>
        <label value="Incrémente"></label>
        <url value="<?php echo $hs_url_module; ?>scan.php?authkey=<?php echo $hs_authkey; ?>&amp;code=&lt;id&gt;&amp;action=add"></url>
        <action value="false"></action>
        <color value="buttonGreen"></color>
    </button>
    <button>
        <label value="Décrémente"></label>
        <url value="<?php echo $hs_url_module; ?>scan.php?authkey=<?php echo $hs_authkey; ?>&amp;code=&lt;id&gt;&amp;action=remove"></url>
        <action value="false"></action>
        <color value="buttonRed"></color>
    </button>
</helloscan>
