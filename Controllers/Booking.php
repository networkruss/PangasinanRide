<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\NotificationsModel;

class Booking extends ResourceController
{
    protected $modelName = 'App\Models\BookingModel';
    protected $format    = 'json';

    public function __construct()
    {
        helper('mail');
    }

    // Get all bookings
    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    // Get a specific booking by ID
    public function show($id = null)
    {
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        return $this->respond($booking);
    }

    // Get bookings by owner UUID
    public function showByOwnerUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('Owner UUID is required');
        }

        $carModel = new \App\Models\CarListingsModel();
        $bookings = $this->model->getBookingsByOwnerUuid($uuid);  // This fetches bookings for the owner UUID
        $owner = new \App\Models\UserInfoModel();

        if (!$bookings) {
            return $this->failNotFound('No bookings found for this owner');
        }

        // Loop through bookings and add car info
        foreach ($bookings as &$booking) {
            // Check if the car exists
            $car = $carModel->find($booking['car_id']);
            if ($car) {
                // Get owner info (owner of the car)
                $ownerInfo = $owner->find($car['owner_uuid']);
                $booking['car_info'] = $car; // Attach car details to the booking
                $booking['owner_info'] = $ownerInfo ? $ownerInfo : null; // Attach owner info, null if not found

                // Get booker info (person who booked)
                $bookerInfo = $owner->find($booking['booker_uuid']);
                $booking['booker_info'] = $bookerInfo ? $bookerInfo : null; // Attach booker info, null if not found
            } else {
                // If car not found, set car_info to null
                $booking['car_info'] = null;
                $booking['owner_info'] = null;
                $booking['booker_info'] = null;
            }
        }

        return $this->respond($bookings);
    }


    // Get bookings by booker UUID
    public function showByBookerUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('Booker UUID is required');
        }

        $carModel = new \App\Models\CarListingsModel();
        $feedbackModel = new \App\Models\FeedbackModel(); // Load the Feedback model
        $bookings = $this->model->getBookingsByBookerUuid($uuid);  // This fetches bookings for the booker UUID
        $owner = new \App\Models\UserInfoModel();

        if (!$bookings) {
            return $this->failNotFound('No bookings found for this booker');
        }

        // Loop through bookings and add car info and feedback check
        foreach ($bookings as &$booking) {
            // Check if the car exists
            $car = $carModel->find($booking['car_id']);
            if ($car) {
                // Get owner info (owner of the car)
                $ownerInfo = $owner->find($car['owner_uuid']);
                $booking['car_info'] = $car; // Attach car details to the booking
                $booking['owner_info'] = $ownerInfo ? $ownerInfo : null; // Attach owner info, null if not found

                // Get booker info (person who booked)
                $bookerInfo = $owner->find($booking['booker_uuid']);
                $booking['booker_info'] = $bookerInfo ? $bookerInfo : null; // Attach booker info, null if not found

                // Check if feedback exists for this booking (using booker_uuid and car_id)
                $feedback = $feedbackModel->alreadyFeedback($uuid, $booking['car_id']);
                $booking['already_feedback'] = $feedback ? true : false; // Set feedback status
            } else {
                // If car not found, set car_info to null
                $booking['car_info'] = null;
                $booking['owner_info'] = null;
                $booking['booker_info'] = null;
                $booking['already_feedback'] = false; // No car means no feedback either
            }
        }

        return $this->respond($bookings);
    }



    // Create or update a booking
    public function saveBooking()
    {
        $data = $this->request->getPost();
        $id = $data['id'] ?? null;
        $notificationModel = new NotificationsModel();

        // Insert or update based on ID
        if ($id) {
            // Update existing booking
            if ($this->model->update($id, $data)) {
                // Notify booker if the booking is approved
                if ($data['status'] === 'approved') {
                    $booking = $this->model->find($id);
                    $notificationModel->insert([
                        'uuid' => $booking['booker_uuid'],
                        'title' => 'Booking Approved',
                        'content' => 'Your booking has been approved by the owner.',
                    ]);
                }
                return $this->respond(['status' => 'Booking updated successfully']);
            }
        } else {
            // Create new booking
            if ($this->model->insert($data)) {
                $newBooking = $this->model->where('car_id', $data['car_id'])
                    ->where('booker_uuid', $data['booker_uuid'])
                    ->first();

                // Notify the owner of the new booking
                $notificationModel->insert([
                    'uuid' => $data['owner_uuid'],
                    'title' => 'New Booking',
                    'content' => 'You have a new booking for your car.',
                ]);

                // Notify the booker that the booking was created
                $notificationModel->insert([
                    'uuid' => $data['booker_uuid'],
                    'title' => 'Booking Created',
                    'content' => 'Your booking has been successfully created.',
                ]);

                $car = new \App\Models\CarListingsModel();
                $carDetails = $car->find($data['car_id']);

                $booker = new \App\Models\UserAuthModel();
                $bookerDetails = $booker->where('uuid', $data['booker_uuid'])->first();

                $booking  = new \App\Models\BookingModel();
                $bookingDetails = $booking->where('car_id', $data['car_id'])->where('booker_uuid', $data['booker_uuid'])->first();

                $emailSubject = "Booking Created";
                $emailBody = "
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
                    .button {
                        display: inline-block;
                        background-color: #4CAF50;
                        color: white !important;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 5px;
                        font-size: 16px;
                        margin-top: 20px;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        color: #888888;
                        font-size: 14px;
                    }
                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>Thank you for creating a booking with Pangasinan Ride. Please see the details below:</p>
                    <p><strong>Car:</strong> $carDetails[brand]</p>
                    <p><strong>Model/Year:</strong> $carDetails[model] / $carDetails[year]</p>
                    <p><strong>Status:</strong> $bookingDetails[status]</p>
                    <p>Please wait for confirmation from the owner.</p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

                sendEmail($bookerDetails['email'], $emailSubject, $emailBody);

                // Notify the owner of the new booking
                $owner = new \App\Models\UserInfoModel();
                $ownerDetails = $owner->where('uuid', $data['owner_uuid'])->first();

                $ownerAuth = new \App\Models\UserAuthModel();
                $ownerAuthDetails = $ownerAuth->where('uuid', $data['owner_uuid'])->first();

                $emailSubject = "New Booking";
                $emailBody = "
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
                    .button {
                        display: inline-block;
                        background-color: #4CAF50;
                        color: white !important;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 5px;
                        font-size: 16px;
                        margin-top: 20px;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        color: #888888;
                        font-size: 14px;
                    }
                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>
                        $ownerDetails[first_name] $ownerDetails[last_name] has created a booking for your car. Please check your dashboard for more details.
                    </p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

                sendEmail($ownerAuthDetails['email'], $emailSubject, $emailBody);
                return $this->respondCreated(['status' => 'Booking created successfully']);
            }
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Delete booking by ID
    public function delete($id = null)
    {
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Proceed to delete the booking
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['status' => 'Booking deleted successfully']);
        }

        return $this->failServerError('Failed to delete booking');
    }

    public function approveBooking($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Booking ID is required');
        }

        // Fetch the booking details
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Fetch the car associated with the booking
        $carModel = new \App\Models\CarListingsModel();
        $car = $carModel->find($booking['car_id']);

        if (!$car) {
            return $this->failNotFound('Car not found');
        }

        // Update the booking status to 'approved'
        $this->model->update($id, ['status' => 'approved']);

        // Update the car's availability to 'booked'
        $carModel->update($booking['car_id'], ['availability' => 'booked']);

        // Notify the booker that the booking has been approved
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $booking['booker_uuid'],
            'title' => 'Booking Approved',
            'content' => 'Your booking has been approved by the owner.',
        ]);

        $bookerAuth = new \App\Models\UserAuthModel();
        $bookerAuthDetails = $bookerAuth->where('uuid', $booking['booker_uuid'])->first();

        $emailSubject = "Booking Approved";
        $emailBody = "
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

                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>Your booking has been approved by the owner. Please check your dashboard for more details.</p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

        sendEmail($bookerAuthDetails['email'], $emailSubject, $emailBody);

        return $this->respond(['status' => 'Booking approved successfully, car availability updated to booked']);
    }

    public function cancelBooking($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Booking ID is required');
        }

        // Fetch the booking details
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Fetch the car associated with the booking
        $carModel = new \App\Models\CarListingsModel();
        $car = $carModel->find($booking['car_id']);

        if (!$car) {
            return $this->failNotFound('Car not found');
        }

        // Update the booking status to 'approved'
        $this->model->update($id, ['status' => 'cancelled']);

        // Update the car's availability to 'booked'
        $carModel->update($booking['car_id'], ['availability' => 'available']);

        // Notify the owner that the booking has been cancelled by the booker
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $booking['owner_uuid'],
            'title' => 'Booking Cancelled',
            'content' => 'Your booking has been cancelled by the booker.',
        ]);

        $bookerAuth = new \App\Models\UserAuthModel();
        $bookerAuthDetails = $bookerAuth->where('uuid', $booking['booker_uuid'])->first();

        $emailSubject = "Booking Cancelled";
        $emailBody = "
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

                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>
                        You cancelled your booking. Please check your dashboard for more details. 
                        If you have any questions, please contact the owner.
                    </p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

        sendEmail($bookerAuthDetails['email'], $emailSubject, $emailBody);
        return $this->respond(['status' => 'Booking approved successfully, car availability updated to booked']);
    }


    // Decline booking by ID
    public function declineBooking($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Booking ID is required');
        }

        // Fetch the booking details
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Fetch the car associated with the booking
        $carModel = new \App\Models\CarListingsModel();
        $car = $carModel->find($booking['car_id']);

        if (!$car) {
            return $this->failNotFound('Car not found');
        }

        // Update the booking status to 'declined'
        $this->model->update($id, ['status' => 'declined']);

        // Update the car's availability back to 'available'
        $carModel->update($booking['car_id'], ['availability' => 'available']);

        // Notify the booker that the booking has been declined
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $booking['booker_uuid'],
            'title' => 'Booking Declined',
            'content' => 'Your booking has been declined by the owner.',
        ]);

        $bookerAuth = new \App\Models\UserAuthModel();
        $bookerAuthDetails = $bookerAuth->where('uuid', $booking['booker_uuid'])->first();

        $emailSubject = "Booking Declined";
        $emailBody = "
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

                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>
                        Your booking has been declined by the owner. Please check your dashboard for more details. 
                        If you have any questions, please contact the owner.
                    </p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

        sendEmail($bookerAuthDetails['email'], $emailSubject, $emailBody);

        return $this->respond(['status' => 'Booking declined successfully, car availability updated to available']);
    }

    public function markBookingCompleted($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Booking ID is required');
        }

        // Fetch the booking details
        $booking = $this->model->getBookingById($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Fetch the car associated with the booking
        $carModel = new \App\Models\CarListingsModel();
        $car = $carModel->find($booking['car_id']);

        if (!$car) {
            return $this->failNotFound('Car not found');
        }

        // Update the booking status to 'completed'
        $this->model->update($id, ['status' => 'completed']);

        // Update the car's availability back to 'available'
        $carModel = new \App\Models\CarListingsModel();
        $car = $carModel->find($booking['car_id']);
        $carModel->update($booking['car_id'], ['availability' => 'available']);

        // Optionally, you could update the car availability based on your business logic
        // For example, if you want to make the car available again after completion:
        // $carModel->update($booking['car_id'], ['availability' => 'available']);

        // Notify the booker that the booking has been completed
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $booking['booker_uuid'],
            'title' => 'Booking Completed',
            'content' => 'Your booking has been marked as completed.',
        ]);

        $bookerAuth = new \App\Models\UserAuthModel();
        $bookerAuthDetails = $bookerAuth->where('uuid', $booking['booker_uuid'])->first();

        $emailSubject = "Booking Completed";
        $emailBody = "
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

                    .logo {
                        width: 170px;
                        height: 120px;
                        margin: 0 auto;
                        display: block;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                   <img src='https://i.imgur.com/JIVpf5A.png' alt='Pangasinan Ride Logo' class='logo'>
                    <h1>$emailSubject</h1>
                    <p>
                        Hello there! Your booking has been marked as completed. You can now leave feedback for the owner.
                        If you have any questions, please contact the owner.
                    </p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

        sendEmail($bookerAuthDetails['email'], $emailSubject, $emailBody);
        return $this->respond(['status' => 'Booking marked as completed successfully']);
    }
}
