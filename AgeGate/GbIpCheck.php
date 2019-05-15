<?php
namespace EighteenPlus\AgeGate;

require("GbIpData.php");

class GbIPCheck{
    public static function IsGB($ip1){
        $ip2 = GbIPCheck::IPToUint32($ip1);

        $len1 = count(ipranges) / 2;

        $a = 0;
        $b = $len1;

        while(true){

            $c = ($a + $b) / 2;

            if( $ip2 >= ipranges[$c*2+0] && $ip2 <= ipranges[$c*2+1] ){
                return true;
            }

            if( $a == $c ){
                return false;
            }

            if( $ip2 < ipranges[$c*2+0] ){
                $b = $c;
                continue;
            }

            if( $ip2 > ipranges[$c*2+1] ){
                $a = $c;
                continue;
            }

            return false;

        }
    }

    static function IPToUint32($ip){

        $ss = explode(".", $ip);

        $i0 = intval($ss[0]);
        $i1 = intval($ss[1]);
        $i2 = intval($ss[2]);
        $i3 = intval($ss[3]);

        $result = ($i0 << 24) | ($i1 << 16) | ($i2 << 8) | $i3;

        return $result;
    }
}