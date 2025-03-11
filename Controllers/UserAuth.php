<?php

namespace App\Controllers;

use App\Models\UserAuthModel;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\HTTP\ResponseInterface;

class UserAuth extends ResourceController
{
    protected $modelName = 'App\Models\UserAuthModel';
    protected $format    = 'json';

    private $jwtKey = 'your_secret_key'; // Use a more secure key in production

    public function __construct()
    {
        helper('mail');
    }

    // List all users (admin can use this)
    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    // Get a specific user by UUID
    public function show($uuid = null)
    {
        $user = $this->model->getUserByUuid($uuid);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        return $this->respond($user);
    }

    public function createProvider()
    {
        $data = $this->request->getPost();
        $ownership = $this->request->getFile('ownership');
        $avatar = $this->request->getFile('avatar');

        // Check if email exists
        if ($this->model->where('email', $data['email'])->first()) {
            return $this->failValidationErrors('Email already exists');
        }

        // Generate unique UUID and hashed password
        $data['uuid'] = uniqid('', true);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['role'] = 'provider';
        $data['verification_token'] = bin2hex(random_bytes(32)); // Generate a random token

        // Handle ownership file upload
        if ($ownership && $ownership->isValid() && !$ownership->hasMoved()) {
            $ownershipValidated = $this->validate([
                'ownership' => [
                    'uploaded[ownership]',
                    'mime_in[ownership,image/jpg,image/jpeg,image/png,image/gif,image/webp,image/avif]',
                    'max_size[ownership,51200]',
                ],
            ]);

            if (!$ownershipValidated) {
                // Handle validation errors
                return $this->fail($this->validator->getErrors(), 400);
            }

            // Generate a new file name to avoid conflicts
            $newOwnerShipName = $ownership->getRandomName();

            // Ensure the directory exists
            $uploadPath = FCPATH . 'uploads/ownership';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move the file to the desired location
            $ownership->move($uploadPath, $newOwnerShipName);

            // Save the file name in the database
            $data['ownership'] = 'uploads/ownership/' . $newOwnerShipName;
        } else {
            // Handle file upload error
            return $this->fail(['ownership' => $ownership->getErrorString()], 400);
        }

        // Handle avatar file upload
        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            $avatarValidated = $this->validate([
                'avatar' => [
                    'uploaded[avatar]',
                    'mime_in[avatar,image/jpg,image/jpeg,image/png,image/gif,image/webp,image/avif]',
                    'max_size[avatar,51200]', // 50MB max
                ],
            ]);

            if (!$avatarValidated) {
                // Handle validation errors
                return $this->fail($this->validator->getErrors(), 400);
            }

            // Generate a new file name to avoid conflicts
            $newAvatarName = $avatar->getRandomName();

            // Ensure the directory exists
            $uploadPath = FCPATH . 'uploads/avatar';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move the file to the desired location
            $avatar->move($uploadPath, $newAvatarName);

            // Save the file name in the database
            $data['avatar'] = 'uploads/avatar/' . $newAvatarName;
        } else {
            // Handle file upload error
            return $this->fail(['avatar' => $avatar->getErrorString()], 400);
        }

        // Insert user data into database
        if ($this->model->insert($data)) {
            // Insert user additional info
            $userInfoModel = new \App\Models\UserInfoModel();
            $userInfoModel->insert([
                'uuid' => $data['uuid'],
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'driver_license' => $data['driverLicense'],
                'driver_license_expiry' => $data['driverLicenseExpiry'],
                'vehicle_plate_number' => $data['vehiclePlateNumber'],
                'ownership' => $data['ownership'],
                'avatar' => $data['avatar'], // Save avatar file path
            ]);

            // Insert user address
            $userAddressModel = new \App\Models\UserAddressModel();
            $userAddressModel->insert([
                'uuid' => $data['uuid'],
                'house_no' => $data['house_no'],
                'street' => $data['street'],
                'barangay' => $data['barangay'],
                'city' => $data['city'],
                'zip' => $data['zip'],
            ]);

            // Generate JWT token for the user
            $token = $this->generateJWT($data['uuid'], $data['email'], $data['role']);

            // Send verification email
            $verificationLink = base_url("verify-email?token={$data['verification_token']}");
            $emailSubject = "Verify Your Email Address";
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
                <h1>Verify Your Email Address</h1>
                <p>Thank you for signing up! To complete your registration, please verify your email address by clicking the button below:</p>
                <a href='$verificationLink' class='button'>Verify Email</a>
                <p>If you did not create an account, no further action is required.</p>
                <div class='footer'>
                    <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                </div>
            </div>
        </body>
        </html>
        ";

            if (sendEmail($data['email'], $emailSubject, $emailBody)) {
                return $this->respondCreated([
                    'status' => 'User created successfully. Please check your email to verify your account.',
                    'token' => $token,
                    'uuid' => $data['uuid'],
                    'role' => $data['role']
                ]);
            } else {
                log_message('error', 'Failed to send verification email to: ' . $data['email']);
                return $this->fail('Failed to send verification email.');
            }
        } else {
            // Handle validation errors in model insert
            return $this->failValidationErrors($this->model->errors());
        }
    }


    public function createClient()
    {
        $data = $this->request->getPost();
        $avatar = $this->request->getFile('avatar');

        // Check if email exists
        if ($this->model->where('email', $data['email'])->first()) {
            return $this->failValidationErrors('Email already exists');
        }

        // Generate unique UUID and hashed password
        $data['uuid'] = uniqid('', true);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['role'] = 'client'; // Default role is client
        $data['verification_token'] = bin2hex(random_bytes(32)); // Generate a random token

        // Handle avatar file upload
        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            $avatarValidated = $this->validate([
                'avatar' => [
                    'uploaded[avatar]',
                    'mime_in[avatar,image/jpg,image/jpeg,image/png,image/gif,image/webp,image/avif]',
                    'max_size[avatar,51200]', // 10MB max
                ],
            ]);

            if (!$avatarValidated) {
                // Handle validation errors
                return $this->fail($this->validator->getErrors(), 400);
            }

            // Generate a new file name to avoid conflicts
            $newAvatarName = $avatar->getRandomName();

            // Ensure the directory exists
            $uploadPath = FCPATH . 'uploads/avatar';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move the file to the desired location
            $avatar->move($uploadPath, $newAvatarName);

            // Save the file name in the database
            $data['avatar'] = 'uploads/avatar/' . $newAvatarName;
        } else {
            // Handle file upload error
            return $this->fail(['avatar' => $avatar->getErrorString()], 400);
        }

        // Insert user data into database
        if ($this->model->insert($data)) {
            // Insert user additional info
            $userInfoModel = new \App\Models\UserInfoModel();
            $userInfoModel->insert([
                'uuid' => $data['uuid'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'avatar' => $data['avatar'], // Save avatar file path
            ]);

            // Insert user address
            $userAddressModel = new \App\Models\UserAddressModel();
            $userAddressModel->insert([
                'uuid' => $data['uuid'],
                'house_no' => $data['house_no'],
                'street' => $data['street'],
                'barangay' => $data['barangay'],
                'city' => $data['city'],
                'zip' => $data['zip'],
            ]);

            // Generate JWT token for the user
            $token = $this->generateJWT($data['uuid'], $data['email'], $data['role']);

            // Send verification email
            $verificationLink = base_url("verify-email?token={$data['verification_token']}");
            $emailSubject = "Verify Your Email Address";
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
                    <h1>Verify Your Email Address</h1>
                    <p>Thank you for signing up! To complete your registration, please verify your email address by clicking the button below:</p>
                    <a href='$verificationLink' class='button'>Verify Email</a>
                    <p>If you did not create an account, no further action is required.</p>
                    <div class='footer'>
                        <p>This email was sent by Pangasinan Ride. If you have any questions, please contact us at <a href='mailto:support@pangasinanride.com'>support@pangasinanride.com</a>.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            if (sendEmail($data['email'], $emailSubject, $emailBody)) {
                return $this->respondCreated([
                    'status' => 'User created successfully. Please check your email to verify your account.',
                    'token' => $token,
                    'uuid' => $data['uuid'],
                    'role' => $data['role']
                ]);
            } else {
                log_message('error', 'Failed to send verification email to: ' . $data['email']);
                return $this->fail('Failed to send verification email.');
            }
        } else {
            // Handle validation errors in model insert
            return $this->failValidationErrors($this->model->errors());
        }
    }

    // Admin registration method
    public function registerAdmin()
    {
        $data = $this->request->getPost();

        // Check if email exists
        if ($this->model->where('email', $data['email'])->first()) {
            return $this->failValidationErrors('Email already exists');
        }

        $data['uuid'] = uniqid('', true);  // Generate unique UUID for new admin
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);  // Hash password
        $data['role'] = 'admin'; // Explicitly set the role as admin

        if ($this->model->insert($data)) {
            // Generate JWT token for the admin
            $token = $this->generateJWT($data['uuid'], $data['email'], $data['role']);

            return $this->respondCreated([
                'status' => 'Admin created successfully',
                'token' => $token,
                'uuid' => $data['uuid'],
                'role' => $data['role']
            ]);
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Login method to authenticate users and issue JWT
    public function login()
    {
        $data = $this->request->getPost();

        $user = $this->model->where('email', $data['email'])->first();

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->failUnauthorized('Invalid email or password');
        }

        // Generate JWT token for the user
        $token = $this->generateJWT($user['uuid'], $user['email'], $user['role']);

        return $this->respond([
            'status' => 'Login successful',
            'token' => $token,
            'uuid' => $user['uuid'],
            'role' => $user['role']
        ], ResponseInterface::HTTP_OK);
    }

    // Update a user's details
    public function update($uuid = null)
    {
        $data = $this->request->getPost();
        $user = $this->model->getUserByUuid($uuid);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Check if 'email' is provided and if it's the same as the existing one
        if (isset($data['email']) && $data['email'] !== null && $user['email'] === $data['email']) {
            return $this->failValidationErrors('This is your existing email');
        }

        // Handle password update if provided
        if (!empty($data['password'])) {
            if (password_verify($data['password'], $user['password'])) {
                return $this->failValidationErrors('The new password cannot be the same as the old one');
            }

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($this->model->update($user['id'], $data)) {
            return $this->respond(['status' => 'User updated successfully']);
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Delete a user
    public function delete($uuid = null)
    {
        $user = $this->model->getUserByUuid($uuid);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        if ($this->model->delete($user['id'])) {
            return $this->respondDeleted(['status' => 'User deleted successfully']);
        }

        return $this->failServerError('Failed to delete the user');
    }

    // Generate JWT token
    private function generateJWT($uuid, $email, $role)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;  // Token valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'uuid' => $uuid,
            'email' => $email,
            'role' => $role, // Add role to the token
        ];

        return JWT::encode($payload, $this->jwtKey, 'HS256');
    }

    public function changePassword()
    {
        $data = $this->request->getPost();
        $user = $this->model->getUserByUuid($data['uuid']);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        if (password_verify($data['password'], $user['password'])) {
            return $this->failValidationErrors('The new password cannot be the same as the old one');
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        if ($this->model->update($user['id'], $data)) {
            return $this->respond(['status' => 'Password changed successfully']);
        }

        return $this->failValidationErrors($this->model->errors());
    }

    public function verifyEmail()
    {
        $token = $this->request->getGet('token');
        if (!$token) {
            return redirect()->to('/verification-failed')->with('error', 'Verification token is required.');
        }
        $userModel = new \App\Models\UserAuthModel();
        $user = $userModel->where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->to('/verification-failed')->with('error', 'Invalid verification token.');
        }
        $userModel->update($user['id'], [
            'verified' => true,
        ]);
        return redirect()->to('http://localhost:5173/account?verified=true&token=' . $token);
    }

    public function removeVerificationToken($uuid = null)
    {
        $userModel = new \App\Models\UserAuthModel();
        $userModel->update($uuid, [
            'verification_token' => null,
        ]);

        return $this->respond(['status' => 'Verification token removed successfully']);
    }

    public function isVerified($uuid = null)
    {
        $userModel = new \App\Models\UserAuthModel();
        $user = $userModel->where('uuid', $uuid)->first();
        return $this->respond($user['verified']);
    }
}
