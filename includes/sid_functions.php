<?php
/*
 * Copyright (c) 2022 SiD Secure EFT
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

function query_sid_order( $order_id, $amount, $merchant_code, $user, $pass, $country, $currency )
{
    $xml_string = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
              <soap:Body>
              <sid_order_query xmlns="http://tempuri.org/"><XML>&lt;?xml version="1.0" encoding="UTF-8"?&gt;&lt;sid_order_query_request&gt;&lt;merchant&gt;&lt;code&gt;' . $merchant_code . '&lt;/code&gt;&lt;uname&gt;' . $user . '&lt;/uname&gt;&lt;pword&gt;' . $pass . '&lt;/pword&gt;&lt;/merchant&gt;&lt;orders&gt;&lt;transaction&gt;&lt;country&gt;' . $country . '&lt;/country&gt;&lt;currency&gt;' . $currency . '&lt;/currency&gt;&lt;amount&gt;' . $amount . '&lt;/amount&gt;&lt;reference&gt;' . $order_id . '&lt;/reference&gt;&lt;/transaction&gt;&lt;/orders&gt;&lt;/sid_order_query_request&gt;</XML></sid_order_query>
              </soap:Body>
              </soap:Envelope>';

    $header = array(
        "Content-Type: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "SOAPAction: http://tempuri.org/sid_order_query",
        "Content-length: " . strlen( $xml_string ) );

    $url     = "https://www.sidpayment.com/api/?wsdl";
    $soap_do = curl_init();
    curl_setopt( $soap_do, CURLOPT_URL, $url );
    curl_setopt( $soap_do, CURLOPT_CONNECTTIMEOUT, 10 );
    curl_setopt( $soap_do, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $soap_do, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $soap_do, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $soap_do, CURLOPT_HEADER, 0 );
    curl_setopt( $soap_do, CURLOPT_POST, true );
    curl_setopt( $soap_do, CURLOPT_POSTFIELDS, $xml_string );
    curl_setopt( $soap_do, CURLOPT_HTTPHEADER, $header );
    $result = curl_exec( $soap_do );
    curl_close( $soap_do );

    return $result;
}
function is_sid_order_successful( $order_id, $amount, $merchant_code, $user, $pass, $country, $currency )
{
    $result_xml = query_sid_order( $order_id, $amount, $merchant_code, $user, $pass, $country, $currency );

    $mystring = $order_id;

    if ( !empty( $result_xml ) && strpos( $result_xml, 'outcome errorcode="0"' ) !== false ) {
        if ( strpos( $result_xml, $mystring ) !== false ) {
            if ( strpos( $result_xml, "COMPLETED" ) !== false ) {
                return true;
            }
        }
    }
    return false;
}
