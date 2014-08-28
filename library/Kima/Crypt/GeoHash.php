<?php
/**
 * Geohash generation class
 * http://blog.dixo.net/downloads/
 *
 * This file copyright (C) 2008 Paul Dixon (paul@elphin.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Kima\Crypt;

/**
 * Latitud and longitud encription libraries
 * Based on https://github.com/AndyA/Geo--Hash/blob/master/php/geohash.class.php
 */
class GeoHash
{
    /**
     * Coding variables
     * @var string
     */
    private $coding = "0123456789bcdefghjkmnpqrstuvwxyz";

    /**
     * Latitud and longitud encode
     * @param  float  $latitude
     * @param  float  $longitude
     * @return string encoded latitude and longitude
     */
    public function encode($latitude, $longitude)
    {
        //how many bits does latitude need?
        $minimum_latitude_presicion = $this->precision($latitude);
        $latitude_bits = 1;
        $error = 45;
        while ($error > $minimum_latitude_presicion) {
            $latitude_bits++;
            $error /= 2;
        }

        //how many bits does longitude need?
        $minimun_longitude_presicion = $this->precision($longitude);
        $longitude_bits = 1;
        $error = 90;
        while ($error > $minimun_longitude_presicion) {
            $longitude_bits++;
            $error /= 2;
        }

        //bit counts need to be equal
        $bits = max($latitude_bits, $longitude_bits);

        //as the hash create bits in groups of 5, lets not
        //waste any bits - lets bulk it up to a multiple of 5
        //and favour the longitude for any odd bits
        $longitude_bits = $bits;
        $latitude_bits = $bits;
        $add_longitude = 1;
        while (($longitude_bits + $latitude_bits) %5 != 0) {
            $longitude_bits += $add_longitude;
            $latitude_bits += !$add_longitude;
            $add_longitude = !$add_longitude;
        }

        //encode each as binary string
        $binary_latitude = $this->binEncode($latitude, -90, 90, $latitude_bits);
        $binary_longitude = $this->binEncode($longitude, -180, 180, $longitude_bits);

        //merge lat and long together
        $binary = "";
        $use_longitude = 1;
        while (strlen($binary_latitude) + strlen($binary_longitude)) {
            if ($use_longitude) {
                $binary = $binary.substr($binary_longitude, 0, 1);
                $binary_longitude = substr($binary_longitude, 1);
            } else {
                $binary = $binary.substr($binary_latitude, 0, 1);
                $binary_latitude = substr($binary_latitude, 1);
            }
            $use_longitude = !$use_longitude;
        }

        //convert binary string to hash
        $hash = "";
        for ($index = 0; $index < strlen($binary); $index += 5) {
            $n = bindec(substr($binary, $index, 5));
            $hash = $hash . $this->coding[$n];
        }

        return $hash;
    }

    /*
    * Returns precision of number
    * precision of 42 is 0.5
    * precision of 42.4 is 0.05
    * precision of 42.41 is 0.005 etc
    */
    private function precision($number)
    {
        $precision = 0;
        $pt = strpos($number, '.');
        if ($pt !== false) {
            $precision=-(strlen($number)-$pt-1);
        }

        return pow(10, $precision) / 2;
    }

    /**
    * Create binary encoding of number as detailed in http://en.wikipedia.org/wiki/Geohash#Example
    * removing the tail recursion is left an exercise for the reader
    */
    private function binEncode($number, $min, $max, $bitcount)
    {
        if ($bitcount == 0) {
            return "";
        }

        #echo "$bitcount: $min $max<br>";

        //this is our mid point - we will produce a bit to say
        //whether $number is above or below this mid point
        $mid = ($min + $max) / 2;
        if ($number>$mid) {
            return "1" . $this->binEncode($number, $mid, $max, $bitcount-1);
        } else {
            return "0" . $this->binEncode($number, $min, $mid, $bitcount-1);
        }
    }
}
