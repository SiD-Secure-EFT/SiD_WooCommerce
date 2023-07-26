<?php
/*
 * Copyright (c) 2023 SiD Secure EFT
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
            $submitRefund = json_decode($this->doAPICall(self::API_BASE . "/refunds", $this->queryArr));
            return $submitRefund->refundId === "3";
        } else {
            return false;
        }
    }

    /**
     * @param $uri
     * @param array $data
     * @return string
     */
    private function doAPICall($uri, array $data = []): string
    {
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->username . ":" . $this->password
        );

        if (!empty($data)) {
            $curlConfig[CURLOPT_POST] = true;
            $curlConfig[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return curl_error($ch);
        }

        return $response;
    }
}
