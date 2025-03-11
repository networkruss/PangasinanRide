<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ClientFeedbackController extends ResourceController
{
    protected $modelName = 'App\Models\ClientFeedbackModel';

    public function __construct()
    {
        helper('mail');
    }

    public function sendFeedbackToClient()
    {
        // Handle JSON input
        if ($this->request->getHeaderLine('Content-Type') === 'application/json') {
            $input = $this->request->getJSON(true);
            $booking_id = $input['booking_id'] ?? null;
            $comment = $input['comment'] ?? null;
        } else {
            $booking_id = $this->request->getPost('booking_id');
            $comment = $this->request->getPost('comment');
        }

        // Add debug logging
        log_message('debug', 'Received booking_id: ' . $booking_id);

        // Cast to integer
        $booking_id = (int)$booking_id;

        $bookingModel = new \App\Models\BookingModel();
        $booking = $bookingModel->find($booking_id);

        log_message('debug', 'Database query result: ' . print_r($booking, true));

        // Safely access array keys with null coalescing
        $booker_uuid = $booking['booker_uuid'] ?? null;
        $owner_uuid = $booking['owner_uuid'] ?? null;
        $car_id = $booking['car_id'] ?? null;

        if (!$booker_uuid || !$owner_uuid || !$car_id) {
            return $this->fail('Incomplete booking data');
        }

        // Prepare feedback data
        $feedbackData = [
            'booking_id'  => $booking_id,
            'booker_uuid' => $booker_uuid,
            'provider_uuid' => $owner_uuid,
            'car_id'      => $car_id,
            'comment'     => $comment,
        ];

        // Save feedback
        try {
            $this->model->insert($feedbackData);
        } catch (\Exception $e) {
            return $this->fail('Failed to submit feedback: ' . $e->getMessage());
        }

        // Get client and owner details
        $userModel = new \App\Models\UserAuthModel();
        $client = $userModel->where('uuid', $booking['booker_uuid'])->first();
        $owner = $userModel->where('uuid', $booking['owner_uuid'])->first();

        // Send email to client
        if ($client && !empty($client['email'])) {
            $this->sendFeedbackEmailToClient(
                $client['email'],
                $comment,
                $owner['email'] ?? ''  // Use empty string if no email found
            );
        }

        return $this->respondCreated([
            'status' => 'success',
            'message' => 'Feedback submitted successfully',
            'data' => $feedbackData
        ]);
    }

    private function sendFeedbackEmailToClient($client_email, $comment, $from_email)
    {
        // Prepare email content
        $subject = 'Feedback from the Car Owner';
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    padding: 20px;
                    background-color: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                }
                h1 {
                    color: #333333;
                    font-size: 24px;
                    margin-bottom: 20px;
                }
                p {
                    color: #555555;
                    font-size: 16px;
                    line-height: 1.6;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    color: #888888;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                <h1>Feedback from the Car Owner</h1>
                <p>You have received feedback from the car owner regarding your recent booking.</p>
                <p><strong>Feedback:</strong> <br /> $comment</p>
                <div class='footer'>
                    <p>This email was sent by Pangasinan Ride. If you have any questions, please contact support.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send the email
        if (sendEmail($client_email, $subject, $message, $from_email)) {
            log_message('info', 'Feedback email sent to client: ' . $client_email);
            return true;
        } else {
            log_message('error', 'Failed to send feedback email to client: ' . $client_email);
            return false;
        }
    }
}
