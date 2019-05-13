<?php
namespace EighteenPlus\AgeGate;

require("autoload.php");
require("GbIpCheck.php");

use \Firebase\JWT\JWT;
use GbIpCheck;



class AgeGate {
    private $AgeCheckURL = "https://deep.reallyme.net/agecheck";
    public $JWT_PUB     = "LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNSUlCQ2dLQ0FRRUF6YjRtcjhqcHh3NXJSU2pqK1NEQQo2cG9GNlFmaXp4dEtUZlVWQTYwTG1XTXJQeS93MWF4KzBsb1lxWWRYT2lVRmhETWhSQ2JiQjVaTmhzcDFEbklnCm03NTdVMldIaXJhOVFQcUNXTmo4Ymo0L1dxN0FwT3hFT0ZQVWFLeTVZZlRjaWQxU3VLWHpZNDNWa21NYUdUYnUKOXFJTWRzcitHU2lTTmdzZlNEcVNIeG4wL0Z5aFFkZTcwbWZjMTh1V3h5ZGVXTm5hRkhjeUZpMWFsbWUyZGREZQpHSlRta043YkZUT2ZHZXM5RkdDZWZzckI3MDRMcE8wcHo2ZjhHNlhsVmZQb0IwY2liWno3SlpHU0g5bHB1RkVkCm5MM2RVRFdvL3BBNzR3REJsSncrVThZWkN3eG1jeFZLVWRwejV1ZUJOMGc1WnN0czhjQjV6Y2V2aHZHSUIzazMKOVFJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==";
    private $uuid; //session ID


    function __construct() {
        session_start();
    }

    function get_client_ip() {
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

    function gen_uuid() {
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

    function makeUrl($uuid) {
        // global $AgeCheckURL;
        $returnURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $postbackURL = $returnURL . "/" . "AgeVerifyResult";
        $url = sprintf("%s?agid=%s&postback=%s&url=%s", $this->AgeCheckURL, urlencode($uuid), urlencode($postbackURL), urlencode($returnURL));

        return $url;
    }

    public function GbIPCheck() {
        return GbIPCheck::IsGB($this->get_client_ip());
    }
    
    public function view() {
        $publicKey = base64_decode($this->JWT_PUB);

        // -------- jwt decode start --------
        // $jwt = JWT::encode($token, $privateKey, 'RS256');
    
        // $decoded = JWT::decode($jwt, $publicKey, array('RS256'));
    
        // $decoded_array = (array) $decoded;
        // -------- jwt decode end --------

    
        if(!isset($_SESSION["agid"])){
            $_SESSION["agid"] = $this->gen_uuid();
        }

        $this->uuid = $_SESSION["agid"];
        $filename = $this->uuid . ".png";
        $deepurl = $this->makeUrl($this->uuid);
    
        $qri = new \Uzulla\QrCode\Image();
        $qri->qrcode_image_out($deepurl, "png", $filename);

        $templatefile = fopen(__DIR__."/template.html", "r") or die("Unable to open file!");
        $template = fread($templatefile,filesize(__DIR__."/template.html"));
        $html = sprintf($template, $filename, $deepurl);
        fclose($templatefile);
        return $html;
    }
   
};