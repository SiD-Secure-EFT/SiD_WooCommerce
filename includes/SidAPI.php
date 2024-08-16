<?php
/*
 * Copyright (c) 2024 SiD Secure EFT
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

class SidAPI
{
    public const API_BASE = "https://www.sidpayment.com/services/api/v30";
    private array $queryArr;
    private string $username;
    private string $password;

    /**
     * @param $queryArr
     * @param $username
     * @param $password
     */
    public function __construct($queryArr, $username, $password)
    {
        $this->queryArr = $queryArr;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return stdClass|null
     */
    public function retrieveTransaction(): ?stdClass
    {
        $apiQuery = self::API_BASE . "/transactions" . $this->buildQueryString();

        return end(json_decode($this->doAPICall($apiQuery))->transactions) ?? null;
    }


    /**
     * @return string
     */
    private function buildQueryString(): string
    {
        $queryString = "/query?";
        foreach ($this->queryArr as $query => $value) {
            $queryString .= $query . "=" . $value . "&";
        }

        return rtrim($queryString, "&");
    }

    /**
     * @param $transactionId
     * @param $amount
     *
     * @return bool
     */
    public function processRefund($transactionId, $amount): bool
    {
        $this->queryArr = [
            "transactionId" => $transactionId,
        ];

        $uri = self::API_BASE . "/refunds" . str_replace("/query", "", $this->buildQueryString());

        $refundReport = json_decode($this->doAPICall($uri));

        if ($refundReport->refundId ?? "" === "1") {
            $this->queryArr["refundAmount"] = $amount;
            $submitRefund                   = json_decode(
                $this->doAPICall(self::API_BASE . "/refunds", $this->queryArr)
            );

            return $submitRefund->refundId === "3";
        } else {
            return false;
        }
    }

    /**
     * @param $uri
     * @param array $data
     *
     * @return string
     */
    private function doAPICall($uri, array $data = []): string
    {
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Content-Type'  => 'application/json',
            ),
            'body'    => !empty($data) ? wp_json_encode($data) : null,
            'method'  => !empty($data) ? 'POST' : 'GET',
        );

        $response = wp_remote_request($uri, $args);

        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        return wp_remote_retrieve_body($response);
    }
}
