<?php
/**
 * AlphaPay - Wechat
 * Author: Keith
 * Copyright (c) VMISS Inc. 2022
 */

// Require libraries needed for gateway module functions.
@require_once ("../../../init.php");
@require_once ("../../../includes/gatewayfunctions.php");
@require_once ("../../../includes/invoicefunctions.php");

// Get the JSON contents
$json = file_get_contents('php://input');
// decode the json data
$data = json_decode($json, true);

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Gateway Configuration Parameters
$partnerCode = $gatewayParams['partnerCode'];
$credentialCode = $gatewayParams['credentialCode'];

$time = $data["time"];
$nonce_str = $data["nonce_str"];
$sign = $data["sign"];
$amount = $data["real_fee"];
$invoiceId = $data["partner_order_id"];
$transactionId = $data["channel_order_id"];
$fee = 0;

// Generate local sign
$_sign = "";
$_sign .= $partnerCode . '&' . $time. '&' . $nonce_str. "&" . $credentialCode;
$_sign = hash('sha256', utf8_encode($_sign));
$_sign = strtolower($_sign);
// Verify sign
if ($sign!==$_sign) die("Invalid Sign");

// Trim string after "-" in the invoiceId: 10-wdgbsfw1uy76 => 10
$invoiceId = substr($invoiceId, 0, strpos($invoiceId, '-'));

// Checks invoice ID is a valid invoice number.
checkCbInvoiceID($invoiceId, $gatewayModuleName);

// Performs a check for any existing transactions with the same given transaction number.
checkCbTransID($transactionId);

// to actual amount
$amount = round($amount / 100, 2);

addInvoicePayment($invoiceId, $transactionId, $amount, $fee, $gatewayModuleName);
logTransaction('AlphaPay - Wechat', $json, "Successful Paid: " . $amount);

?>