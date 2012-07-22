<?php
/**
 * User: jot
 * Date: 7/22/12
 * Time: 11:05 AM
 */
class APIAuth
{
    public static function getAuditFromKey($key) {

        $keys = parse_ini_file('apikeys', false);
        return isset($keys[$key]) ? $keys[$key] : null;
    }
}
