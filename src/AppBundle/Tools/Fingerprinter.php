<?php

namespace AppBundle\Tools;

class Fingerprinter
{
    public static function fingerprint($string)
    {
        return substr(base_convert(crc32('k3y%'.$string), 10, 35), 0, 8);
    }
}
