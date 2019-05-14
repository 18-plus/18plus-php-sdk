<?php
namespace EighteenPlus\AgeGate;

require("GbIpCheck.php");

use \Firebase\JWT\JWT;

class AgeGate {
    static $AgeCheckURL = "https://deep.reallyme.net/agecheck";
    static $JWT_PUB     = "LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNSUlCQ2dLQ0FRRUF6YjRtcjhqcHh3NXJSU2pqK1NEQQo2cG9GNlFmaXp4dEtUZlVWQTYwTG1XTXJQeS93MWF4KzBsb1lxWWRYT2lVRmhETWhSQ2JiQjVaTmhzcDFEbklnCm03NTdVMldIaXJhOVFQcUNXTmo4Ymo0L1dxN0FwT3hFT0ZQVWFLeTVZZlRjaWQxU3VLWHpZNDNWa21NYUdUYnUKOXFJTWRzcitHU2lTTmdzZlNEcVNIeG4wL0Z5aFFkZTcwbWZjMTh1V3h5ZGVXTm5hRkhjeUZpMWFsbWUyZGREZQpHSlRta043YkZUT2ZHZXM5RkdDZWZzckI3MDRMcE8wcHo2ZjhHNlhsVmZQb0IwY2liWno3SlpHU0g5bHB1RkVkCm5MM2RVRFdvL3BBNzR3REJsSncrVThZWkN3eG1jeFZLVWRwejV1ZUJOMGc1WnN0czhjQjV6Y2V2aHZHSUIzazMKOVFJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==";

    function __construct() {
        session_start();
    }

    static function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    static function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    static function makeUrl($uuid) {
        // global $AgeCheckURL;
        $returnURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $postbackURL = $returnURL . "/" . "ReturnAgeVerifyResult";
        $url = sprintf("%s?agid=%s&postback=%s&url=%s", static::$AgeCheckURL, urlencode($_SESSION["agid"]), urlencode($postbackURL), urlencode($returnURL));

        return $url;
    }

    public static function GbIPCheck() {
        return GbIPCheck::IsGB($this->get_client_ip());
    }
    
    public static function view($logourl, $redirectPath = 'AgeVerifyResult') {

        if(!isset($_SESSION["agid"])){
            $_SESSION["agid"] = self::gen_uuid();
        }

        if(!file_exists("/qr")){
            mkdir("/qr");
        }
        $filename = "/qr/" . $_SESSION["agid"] . ".png";
        $deepurl = self::makeUrl($_SESSION["agid"]);
    
        $qri = new \Uzulla\QrCode\Image();
        $qri->qrcode_image_out($deepurl, "png", $filename);

        $im = imagecreatefrompng(__DIR__."/logo.png");
        ob_start(); // Start buffering the output
        imagepng($im, null, 0, PNG_NO_FILTER);
        $b64 = base64_encode(ob_get_contents()); // Get what we've just outputted and base64 it
        imagedestroy($im);
        ob_end_clean();

        $templatefile = fopen(__DIR__."/template.html", "r") or die("Unable to open file!");
        $template = fread($templatefile,filesize(__DIR__."/template.html"));
        $html = sprintf($template, $logourl, "data:image/png;base64,".$b64, $deepurl, "/qr/".$_SESSION["agid"].".png");
        fclose($templatefile);
        return $html;
    }

    public static function verify($jwt) {
        // -------- jwt decode start --------
        // $jwt = JWT::encode($token, $privateKey, 'RS256');
    
        $publicKey = base64_decode(static::$JWT_PUB);
        $decoded = JWT::decode($jwt, $publicKey, array('RS256'));
    
        $decoded_array = (array) $decoded;
        // -------- jwt decode end --------
        return true;
    }
};