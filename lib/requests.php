<?php
/**
 * User: jot@internetgruppen.dk
 * Date: 7/22/12
 * Time: 12:20 PM
 */

function sendsms($from, $to, $message, $audit) {
    $params = array(
        'from' => $from,
        'to' => $to,
        'audit_name' => $audit,
        'message' => $message
    );

    $sms = new SMS($params);

    if (!$sms->send())
        throw new ErrorException("Communication with SMS-gateway failed, gateway responded:\n\n". $sms->getGatewayResponse());

    echo "Send request completed successfully!";
}
