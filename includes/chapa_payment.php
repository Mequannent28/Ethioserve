<?php
/**
 * Chapa Payment Integration Helper
 * Official Chapa API Documentation: https://developer.chapa.co/docs
 */

class ChapaPayment
{
    private $secret_key;
    private $public_key;
    private $base_url = 'https://api.chapa.co/v1';

    public function __construct()
    {
        // TODO: Add your Chapa API keys here
        // Get them from: https://dashboard.chapa.co/
        $this->secret_key = 'CHASECK_TEST-xxxxxxxxxxxxxxxxxxxxxx'; // Replace with actual key
        $this->public_key = 'CHAPUBK_TEST-xxxxxxxxxxxxxxxxxxxxxx'; // Replace with actual key
    }

    /**
     * Initialize a payment
     */
    public function initializePayment($data)
    {
        $url = $this->base_url . '/transaction/initialize';

        $payload = [
            'amount' => $data['amount'],
            'currency' => 'ETB',
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'tx_ref' => $data['tx_ref'], // Unique transaction reference
            'callback_url' => $data['callback_url'],
            'return_url' => $data['return_url'],
            'customization' => [
                'title' => 'EthioServe Bus Ticket',
                'description' => $data['description'] ?? 'Bus ticket payment'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secret_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return [
            'status' => 'failed',
            'message' => 'Payment initialization failed',
            'raw_response' => $response
        ];
    }

    /**
     * Verify a payment
     */
    public function verifyPayment($tx_ref)
    {
        $url = $this->base_url . '/transaction/verify/' . $tx_ref;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secret_key
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return [
            'status' => 'failed',
            'message' => 'Payment verification failed'
        ];
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($tx_ref)
    {
        $verification = $this->verifyPayment($tx_ref);

        if (isset($verification['status']) && $verification['status'] === 'success') {
            return [
                'success' => true,
                'data' => $verification['data']
            ];
        }

        return [
            'success' => false,
            'message' => $verification['message'] ?? 'Payment not found'
        ];
    }
}

/**
 * Helper function to create Chapa payment
 */
function createChapaPayment($booking_data)
{
    $chapa = new ChapaPayment();

    $payment_data = [
        'amount' => $booking_data['total_amount'],
        'email' => $booking_data['email'],
        'first_name' => $booking_data['first_name'],
        'last_name' => $booking_data['last_name'],
        'phone_number' => $booking_data['phone'],
        'tx_ref' => $booking_data['tx_ref'],
        'callback_url' => $booking_data['callback_url'],
        'return_url' => $booking_data['return_url'],
        'description' => $booking_data['description'] ?? 'Bus Ticket Payment'
    ];

    return $chapa->initializePayment($payment_data);
}

/**
 * Helper function to verify Chapa payment
 */
function verifyChapaPayment($tx_ref)
{
    $chapa = new ChapaPayment();
    return $chapa->verifyPayment($tx_ref);
}
?>