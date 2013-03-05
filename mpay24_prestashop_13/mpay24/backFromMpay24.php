<?php
/**
 * @author              support@mpay24.com
 * @filesource          backFromMpay24.php
 * @license             http://ec.europa.eu/idabc/eupl.html EUPL, Version 1.1
 */
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/mpay24.php');
include_once("api/prestaShop.php");

		if(Configuration::get('MPAY24_TEST_MODE') == "true")
			$mode = true;
		else
			$mode = false;
		 
		$prestaShop = new prestaShop(Configuration::get('MPAY24_MERCHANT_ID'), Configuration::get('MPAY24_SOAP_PASS'), $mode, Configuration::get('MPAY24_PROXY_HOST'), Configuration::get('MPAY24_PROXY_PORT'));
		$prestaShop->setCustomer(new Customer((int)$_REQUEST['customerID']), $_REQUEST['cartID']);
		$result = $prestaShop->updateTransactionStatus($_REQUEST['TID']);
		$args = $result->getParams();
		
		$order = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'mpay24_order` WHERE `TID`LIKE "'.$_REQUEST['TID'].'"');
		
		if(substr($args['USER_FIELD'], strrpos($args['USER_FIELD'], " ")) == substr($order[0]['MPAYTID'], strrpos($order[0]['MPAYTID'], " "))) {
			$result = $prestaShop->confirm($_REQUEST['TID'], $args);

			Db::getInstance()->Execute("
					UPDATE `"._DB_PREFIX_."mpay24_order` SET
					`MPAYTID` = ".$args['MPAYTID'].",
					`STATUS` = '".$args['STATUS']."'
					WHERE `TID` = '".$_REQUEST['TID']."'
					");
		} elseif(!is_numeric($order[0]['MPAYTID'])){
			Db::getInstance()->Execute("
					UPDATE `"._DB_PREFIX_."mpay24_order` SET
					`STATUS` = 'ERROR'
					WHERE `TID` = '".$_REQUEST['TID']."'
					");
			$order = new Order((int)$_REQUEST['TID']);
			$order->setCurrentState(_PS_OS_ERROR_);
		}
		header('Location: ' . __PS_BASE_URI__ . "history.php") ;
?>