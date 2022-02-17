<?php
/**
 * AlphaPay - Alipay
 * Author: Keith
 * Copyright (c) VMISS Inc. 2022
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once realpath(dirname(__FILE__)) . "/alphapay/common.php";

function alphapayalipay_MetaData()
{
    return array(
        'DisplayName' => 'AlphaPay - Alipay',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function alphapayalipay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'AlphaPay - Alipay',
        ),
        // a text field type allows for single line text input
        'partnerCode' => array(
            'FriendlyName' => 'Partner Code',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your AlphaPay partner code here',
        ),
        // a password field type allows for masked text input
        'credentialCode' => array(
            'FriendlyName' => 'Credential Code',
            'Type' => 'password',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Enter your AlphaPay credential code here here',
        ),
    );
}


function alphapayalipay_link($params)
{
    // Gateway Configuration Parameters
    $partnerCode = $params['partnerCode'];
    $credentialCode = $params['credentialCode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // System Parameters
    $systemUrl = $params['systemurl'];

    // AlphaPay Parameters
    $time = AlphaPay::getMillisecond();
    $nonce_str = AlphaPay::getNonceStr();
    $sign = AlphaPay::makeSign($partnerCode, $credentialCode, $time, $nonce_str);

    $url_base = 'https://pay.alphapay.ca/api/v1.0/gateway/partners/' . $partnerCode . '/orders/' . $invoiceId . AlphaPay::getInvoiceStr();
    $url_query = AlphaPay::makeQueryParams($time, $nonce_str, $sign);
    $url_qr = $url_base . $url_query;

    $postfields = array (
        'channel' => 'Alipay',
        'currency' => $currencyCode,
        'description' => $description,
        'notify_url' => $systemUrl . 'modules/gateways/callback/alphapayalipay.php',
        'operator' => 'VMISS - Alipay',
        'price' => round($amount * 100, 2),
    );

    $ch = curl_init();
    // time out
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_URL, $url_qr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    // set header
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    // PUT
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));

    $data = curl_exec($ch);

    // response
    if ($data) {
        curl_close($ch);
        // string to json
        $data = json_decode($data, true);

        if ($data['return_code'] === 'SUCCESS') {
            $url_code = urlencode($data['code_url']);
            $htmlOutput = '<img src="modules/gateways/alphapay/qrcode.php?data=' . $url_code .'" style="width:150px;height:150px;"/>';
            return $htmlOutput;
        } else {
            return $data['message'];
        }
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        throw new AlphaPayException("curl error，error code:$error");
    }
}