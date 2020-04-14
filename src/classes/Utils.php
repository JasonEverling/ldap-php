<?php
// Copyright (C) 2020  Jason A. Everling
//
//This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

class Utils
{

    public static function datetime($format)
    {
        $dt = new \DateTime();
        return $dt->format($format);
    }

    public static function now()
    {
        return self::datetime('Y-m-d H:i:s');
    }

    /** Will perform case conversion on object/array values or string
     *
     * @param array|string $data The string or array to lowercase
     * @return array|string
     */
    public static function lc($data)
    {
        if (is_array($data) || is_object($data) || is_string($data)) {
            if (is_array($data) || is_object($data)) {
                $result = self::arrayToLowerCase($data);
            }
            if (is_string($data)) {
                $result = strtolower($data);
            }
        } else {
            error_log('Utils Warning: The input $data is not of type array/object or string');
        }
        return $result;
    }

    /** Will perform case conversion on object/array values or string
     *
     * @param array|string $data The string or array to uppercase
     * @return array|string
     */
    public static function uc($data)
    {
        if (is_array($data) || is_object($data) || is_string($data)) {
            if (is_array($data) || is_object($data)) {
                $result = self::arrayToUpperCase($data);
            }
            if (is_string($data)) {
                $result = strtoupper($data);
            }
        } else {
            error_log('Utils Warning: The input $data is not of type array/object or string');
        }
        return $result;
    }

    /** Change array key case to lower (default) or upper case
     *
     * @param array $array They array to be changed
     * @param string $case lc or uc
     * @return array The modified array
     */
    public static function arrayKeysCase($array, $case = 'lc')
    {
        $change = 0;
        if ($case == 'uc') {
            $change = 1;
        }
        $multi_array = false;
        if (is_array($array) && isset($array[0])) {
            $multi_array = true;
        }
        if ($multi_array) {
            $result = self::arrayOfArraysKeysCase($array, $case);
        } else {
            $result = array_change_key_case($array, $change);
        }
        return $result;
    }

    /** Change multi-dimensional array key case to lower (default) or upper case
     *  This should not be used out side class, arrayKeysCase will automatically
     *  detect multi-dimensional arrays and convert as needed.
     *
     * @param array $array The multi-dimensional array to be changed
     * @param string $case lc(default) or uc
     * @return array The modified multi-dimensional array
     */
    private static function arrayOfArraysKeysCase($array, $case = 'lc')
    {
        $change = 0;
        if ($case == 'uc') {
            $change = 1;
        }
        return array_map(function ($item) {
            if (is_array($item)) {
                $item = self::arrayOfArraysKeysCase($item);
            }
            return $item;
        }, array_change_key_case($array, $change));
    }

    private static function arrayToLowerCase($array)
    {
        return array_map('strtolower', $array);
    }

    private static function arrayToUpperCase($array)
    {
        return array_map('strtoupper', $array);
    }

    public static function arrayMerge($array1, $array2)
    {
        return array_merge($array1, $array2);
    }

    public static function arrayToJson($array)
    {
        return json_encode($array);
    }

    public static function jsonToArray($json)
    {
        return json_decode($json, true);
    }

}
