<?php
/**
 * User: jot@internetgruppen.dk
 * Date: 7/22/12
 * Time: 10:25 AM
 */

error_reporting(0);
ini_set('display_errors', "off");

require_once 'smsrest.prereqs.php';
require_once 'lib/http.php';
require_once 'lib/apiauth.php';
require_once 'lib/requests.php';
require_once 'lib/securityexception.php';
require_once 'lib/logger.php';


try
{
    $audit = APIAuth::getAuditFromKey($_GET['apikey']);

    if (!$audit)
        throw new SecurityException('access for apikey denied');

    switch ($_GET['request']) {
        case 'sendsms':
            $sms = new SMS();
            $sms->to = explode(',', $_GET['to']);
            $sms->audit_name = $audit;
            $sms->message = $_GET['message'];
            echo $sms->send();
            break;

        default:
            throw new InvalidArgumentException('Invalid request "'. $_GET['request']. '"');
    }

} catch (InvalidArgumentException $e) {
    HTTP::respond(HTTP::BADREQUEST);
    echo $e->getMessage();
} catch (SecurityException $e) {
    HTTP::respond(HTTP::FORBIDDEN);
    echo $e->getMessage();
} catch (Exception $e) {
    HTTP::respond(HTTP::INTERNALSERVERERROR);
    echo $e->getMessage();
}
