<?php

class AlphaPay {
    public static function getMillisecond() {
        $time = explode(" ", microtime());
        $millisecond = "000" . ($time[0] * 1000);
        $millisecond2 = explode(".", $millisecond);
        $millisecond = substr($millisecond2[0], -3);
        $time = $time[1] . $millisecond;
        return $time;
    }

    public static function getInvoiceStr($length = 12) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "-";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function getNonceStr($length = 30) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function makeSignParams($partner_code, $credential_code, $time, $nonce_str) {
        $buff = "";
        $buff .= $partner_code . '&' . $time. '&' . $nonce_str. "&" . $credential_code;
        return $buff;
    }


    public static function makeSign($partner_code, $credential_code, $time, $nonce_str) {
        $string = self::makeSignParams($partner_code, $credential_code, $time, $nonce_str);
        $string = hash('sha256', utf8_encode($string));
        $result = strtolower($string);
        return $result;
    }

    public static function makeQueryParams($time, $nonce_str, $sign) {
        $buff = "?";
        $buff .= 'time=' . $time. '&nonce_str=' . $nonce_str . '&sign=' . $sign;
        return $buff;
    }
}
