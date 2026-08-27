<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * authorize.net payment gateway plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2025 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet;

/**
 * Helper class for interacting with the Authorize.Net API (REST).
 */
class authorizedotnet_helper {
    /**
     * API login ID for Authorize.Net.
     *
     * @var string
     */
    private string $apiloginid;

    /**
     * Transaction key for Authorize.Net.
     *
     * @var string
     */
    private string $transactionkey;

    /**
     * Whether to use sandbox mode.
     *
     * @var bool
     */
    private bool $sandbox;

    /**
     * Constructor for Authorize.Net helper.
     *
     * @param string $apiloginid API login ID.
     * @param string $transactionkey Transaction key.
     * @param bool $sandbox Use sandbox environment if true.
     */
    public function __construct(string $apiloginid, string $transactionkey, bool $sandbox) {
        $this->apiloginid = $apiloginid;
        $this->transactionkey = $transactionkey;
        $this->sandbox = $sandbox;
    }

    /**
     * Returns the Authorize.Net API endpoint for the configured environment.
     *
     * @return string
     */
    private function get_api_url(): string {
        return $this->sandbox
            ? 'https://apitest.authorize.net/xml/v1/request.api'
            : 'https://api.authorize.net/xml/v1/request.api';
    }

    /**
     * Posts a JSON payload to the Authorize.Net API and decodes the response.
     *
     * @param array $payload Request payload.
     * @return array|null Decoded response, or null on transport/decode failure.
     */
    private function post(array $payload): ?array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl();
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER'     => ['Content-Type: application/json'],
            'CURLOPT_TIMEOUT'        => 30,
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ];

        $response = $curl->post($this->get_api_url(), json_encode($payload), $options);
        if ($response === false) {
            return null;
        }

        // Authorize.Net prefixes responses with a UTF-8 byte order mark.
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $result = json_decode(trim($response), true);

        return json_last_error() === JSON_ERROR_NONE ? $result : null;
    }

    /**
     * Get merchant details (including currency) from Authorize.Net.
     *
     * @return string
     */
    public function get_merchant_currency(): string {
        $payload = [
            'getMerchantDetailsRequest' => [
                'merchantAuthentication' => [
                    'name'           => $this->apiloginid,
                    'transactionKey' => $this->transactionkey,
                ],
            ],
        ];

        $result = $this->post($payload);
        $currencies = $result['currencies'] ?? [];

        return is_array($currencies) && !empty($currencies) ? $currencies[0] : '';
    }

    /**
     * Creates a transaction using the Authorize.Net REST API.
     *
     * @param float $amount Transaction amount.
     * @param object $opaquedata Opaque data object from Accept.js (descriptor + value).
     * @return array Transaction result. Always contains 'success'; on success also
     *               'transactionid', 'status', 'responsecode', 'authcode', 'invoicenum',
     *               'responsereasoncode' and 'responsereasontext'; on failure also 'message'.
     */
    public function create_transaction(float $amount, object $opaquedata): array {
        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [
                    'name'           => $this->apiloginid,
                    'transactionKey' => $this->transactionkey,
                ],
                'refId' => 'ref' . time(),
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => $amount,
                    'payment' => [
                        'opaqueData' => [
                            'dataDescriptor' => $opaquedata->dataDescriptor,
                            'dataValue'      => $opaquedata->dataValue,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->post($payload);
        if ($result === null) {
            return ['success' => false, 'message' => get_string('noresponse', 'enrol_authorizedotnet')];
        }

        $messages = $result['messages'] ?? null;
        if (!$messages || $messages['resultCode'] !== 'Ok') {
            $message = $messages['message'][0]['text'] ?? get_string('transactionfailmsg', 'enrol_authorizedotnet');
            return ['success' => false, 'message' => $message];
        }

        $tresponse = $result['transactionResponse'] ?? [];
        $responsecode = isset($tresponse['responseCode']) ? (int) $tresponse['responseCode'] : 0;

        if ($responsecode === 1) {
            $reasoncode = $tresponse['messages'][0]['code'] ?? '';
            return [
                'success'            => true,
                'transactionid'      => $tresponse['transId'] ?? '',
                'status'             => $tresponse['messages'][0]['description'] ?? 'Approved',
                'responsecode'       => $responsecode,
                'authcode'           => $tresponse['authCode'] ?? '',
                'invoicenum'         => $tresponse['order']['invoiceNumber'] ?? '',
                'responsereasoncode' => (int) preg_replace('/\D/', '', $reasoncode) ?: 0,
                'responsereasontext' => $tresponse['messages'][0]['description'] ?? '',
            ];
        }

        $error = $tresponse['errors'][0] ?? null;
        $errorcode = $error['errorCode'] ?? '';
        return [
            'success'            => false,
            'message'            => $error['errorText'] ?? get_string('transactionfailmsg', 'enrol_authorizedotnet'),
            'responsecode'       => $responsecode,
            'responsereasoncode' => (int) preg_replace('/\D/', '', $errorcode) ?: 0,
            'responsereasontext' => $error['errorText'] ?? '',
        ];
    }
}
