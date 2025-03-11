<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\NotificationsModel;

class UserInfo extends ResourceController
{
    protected $modelName = 'App\Models\UserInfoModel';
    protected $format    = 'json';

    // Get all users info
    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    // Get a specific user's info by UUID
    public function show($uuid = null)
    {
        $userInfo = $this->model->getUserInfoByUuid($uuid);

        if (!$userInfo) {
            return $this->failNotFound('User info not found');
        }

        return $this->respond($userInfo);
    }

    public function verifyUserExists($uuid = null)
    {
        $userAuthModel = new \App\Models\UserAuthModel();
        $userAuth = $userAuthModel->getUserByUuid($uuid);

        if (!$userAuth) {
            return $this->failNotFound('User not found');
        }
        return $this->respond(['status' => 'User exists', 'verified' => $userAuth['verified'], 'role' => $userAuth['role']]);
    }

    public function getAllUsersWithInfo()
    {
        // Load the models for both UserAuth, UserInfo, and UserAddress
        $userAuthModel = new \App\Models\UserAuthModel();
        $userInfoModel = new \App\Models\UserInfoModel();
        $userAddressModel = new \App\Models\UserAddressModel();

        // Get all users from UserAuth
        $usersAuth = $userAuthModel->findAll();

        // Initialize an array to store users with info
        $usersWithInfo = [];

        // Loop through each user in the auth table
        foreach ($usersAuth as $userAuth) {
            // Get the user info from the UserInfo table by UUID
            $userInfo = $userInfoModel->getUserInfoByUuid($userAuth['uuid']);

            // Get the user address from the UserAddress table by UUID
            $userAddress = $userAddressModel->getUserAddressByUuid($userAuth['uuid']);

            // Combine auth, info, and address data
            $userWithInfo = [
                'uuid' => $userAuth['uuid'],
                'role' => $userAuth['role'],
                'email' => $userAuth['email'],
                'verified' => $userAuth['verified'],
                'created_at' => $userAuth['created_at'],
                'updated_at' => $userAuth['updated_at'],
                'user_info' => $userInfo ? $userInfo : [],
                'user_address' => $userAddress ? $userAddress : [],
            ];

            // Add to the result array
            $usersWithInfo[] = $userWithInfo;
        }

        // Respond with the combined data
        return $this->respond($usersWithInfo);
    }


    // Create new user info
    public function saveUserInfo()
    {
        $data = $this->request->getPost();
        $uuid = $data['uuid'];

        if (!$uuid) {
            return $this->failValidationErrors(['uuid' => 'UUID is required']);
        }

        // Check if user info exists
        $userInfo_ = new \App\Models\UserInfoModel();
        $userInfo = $userInfo_->where('uuid', $uuid)->first();

        // Handle avatar upload
        $file = $this->request->getFile('avatar');
        $ownership = $this->request->getFile('ownership');

        if ($file && $ownership && $file->isValid() && $ownership->isValid() && !$file->hasMoved() && !$ownership->hasMoved()) {
            // Validate the file
            $validated = $this->validate([
                'avatar' => [
                    'uploaded[avatar]',
                    'mime_in[avatar,image/jpg,image/jpeg,image/png,image/gif,image/webp,image/avif]',
                    'max_size[avatar,10240]', // 10MB max
                ],
            ]);

            $ownershipValidated = $this->validate([
                'ownership' => [
                    'uploaded[ownership]',
                    'mime_in[ownership,image/jpg,image/jpeg,image/png,image/gif,image/webp,image/avif]',
                    'max_size[ownership,10240]',
                ],
            ]);

            if ($validated && $ownershipValidated) {
                // Generate a new file name to avoid conflicts
                $newName = $file->getRandomName();
                $newOwnerShipName = $ownership->getRandomName();
                // Move the file to the desired location
                $file->move(FCPATH . 'uploads/users', $newName);
                $ownership->move(FCPATH . 'uploads/ownership', $newOwnerShipName);
                // Store the file path in the database
                $data['avatar'] = 'uploads/users/' . $newName;
                $data['ownership'] = 'uploads/ownership/' . $newOwnerShipName;

                // Optionally delete the old avatar file if updating
                if ($userInfo && !empty($userInfo['avatar']) && file_exists(FCPATH . $userInfo['avatar']) && !empty($userInfo['ownership']) && file_exists(FCPATH . $userInfo['ownership'])) {
                    unlink(FCPATH . $userInfo['avatar']);
                    unlink(FCPATH . $userInfo['ownership']);
                    $data['avatar'] = $userInfo['avatar'];
                    $data['ownership'] = $userInfo['ownership'];
                }

                // Update the user info with the new avatar path
                $data['avatar'] = $data['avatar'];
                $data['ownership'] = $data['ownership'];
            } else {
                // Handle validation errors
                return $this->fail($this->validator->getErrors(), 400);
            }
        } elseif ($file && !$file->isValid() && $ownership && !$ownership->isvalid()) {
            // Handle file upload error only if the file was intended to be uploaded
            return $this->fail(['avatar' => $file->getErrorString(), 'ownsership' => $ownership->getErrorString()], 400);
        }

        // Insert or update based on the existence of uuid
        if ($userInfo) {
            // Update existing user info
            if ($this->model->update($uuid, $data)) {
                return $this->respond(['status' => 'User info updated successfully'], 200);
            } else {
                return $this->failValidationErrors($this->model->errors(), 400);
            }
        } else {
            $this->model->insert($data);
            return $this->respond(['status' => 'User info created successfully'], 201);
        }
    }

    // Delete user info by UUID
    public function delete($uuid = null)
    {
        // Fetch the user info by UUID
        $userInfo = $this->model->getUserInfoByUuid($uuid);

        if (!$userInfo) {
            return $this->failNotFound('User info not found');
        }

        // Check if the user has an avatar and delete the file
        if (!empty($userInfo['avatar']) && file_exists(FCPATH . $userInfo['avatar'])) {
            unlink(FCPATH . $userInfo['avatar']); // Delete the avatar file
        }

        // Proceed to delete the user info from the database
        if ($this->model->delete($uuid)) {
            return $this->respondDeleted(['status' => 'User info and avatar deleted successfully']);
        }

        return $this->failServerError('Failed to delete user info');
    }

    // Verify a user by UUID
    public function verifyUser($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('UUID is required');
        }

        $userAuthModel = new \App\Models\UserAuthModel();
        $userAuth = $userAuthModel->getUserByUuid($uuid);

        if (!$userAuth) {
            return $this->failNotFound('User not found');
        }

        // Ensure the correct primary key is used for the update
        if ($userAuthModel->update($userAuth['id'], ['verified' => 1])) {

            $notificationModel = new NotificationsModel();
            // Notify the owner that the payment is completed
            $notificationModel->insert([
                'uuid' => $uuid,
                'title' => 'Account Verified',
                'content' => 'Your account has been verified.',
            ]);
            return $this->respond(['status' => 'User verified successfully']);
        }

        return $this->failServerError('Failed to verify user');
    }

    public function declineUser($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('UUID is required');
        }

        $userAuthModel = new \App\Models\UserAuthModel();
        $userAuth = $userAuthModel->getUserByUuid($uuid);

        if (!$userAuth) {
            return $this->failNotFound('User not found');
        }

        // Ensure the correct primary key is used for the update
        if ($userAuthModel->update($userAuth['id'], ['verified' => 0])) {
            return $this->respond(['status' => 'User declined successfully']);
        }

        return $this->failServerError('Failed to decline user');
    }


    public function getUserInfoVerified($uuid = null)
    {
        $userInfo_ = new \App\Models\UserInfoModel();

        if ($userInfo_->where('uuid', $uuid)->first()) {
            return $this->respond(['status' => 200]);
        }
        return $this->respond(['status' => 400]);
    }
}
