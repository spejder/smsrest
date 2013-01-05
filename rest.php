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
require_once 'lib/exceptions.php';
require_once 'lib/logger.php';

try
{
    $audit = APIAuth::getAuditFromKey($_REQUEST['apikey']);

    if (!$audit)
        throw new SecurityException('Access for apikey denied');

    switch ($_GET['operation']) {
        case 'sendsms':
            if (HTTP::getRequestMethod() != HTTP::REQUEST_POST)
                throw new HttpMethodException('Operation supports POST-verb only');
            
            $sms = new SMS();
            $sms->to = explode(',', $_POST['to']);
            $sms->audit_name = $audit;
            $sms->message = $_POST['message'];
            echo $sms->send();
            break;

        default:
            throw new InvalidArgumentException('Invalid request "'. $_GET['operation']. '"');
    }

} catch (InvalidArgumentException $e) {
    HTTP::respond(HTTP::RESPONSE_BADREQUEST);
    echo $e->getMessage();
} catch (HttpMethodException $e) {
    HTTP::respond(HTTP::RESPONSE_METHODNOTALLOWED);
    echo $e->getMessage();
} catch (SecurityException $e) {
    HTTP::respond(HTTP::RESPONSE_FORBIDDEN);
    echo $e->getMessage();
} catch (Exception $e) {
    HTTP::respond(HTTP::RESPONSE_INTERNALSERVERERROR);
    echo $e->getMessage();
}

echo "\r\n";
