<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class UserAddress extends ResourceController
{
    protected $modelName = 'App\Models\UserAddressModel';
    protected $format    = 'json';

    // Get all addresses
    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    // Get a specific address by UUID
    public function show($uuid = null)
    {
        $address = $this->model->getAddressByUuid($uuid);

        if (!$address) {
            return $this->failNotFound('Address not found');
        }

        return $this->respond($address);
    }

    // Create or Update address based on UUID
    public function saveAddress()
    {
        $data = $this->request->getPost();
        $uuid = $data['uuid'] ?? null;

        if (!$uuid) {
            return $this->failValidationErrors(['uuid' => 'UUID is required']);
        }

        // Check if the address already exists
        $address = $this->model->getAddressByUuid($uuid);

        // Insert or update the address
        if ($address) {
            // Update the existing address
            if ($this->model->update($uuid, $data)) {
                return $this->respond(['status' => 'Address updated successfully']);
            }
        } else {
            $this->model->insert($data);
            return $this->respondCreated(['status' => 'Address created successfully']);
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Delete address by UUID
    public function delete($uuid = null)
    {
        $address = $this->model->getAddressByUuid($uuid);

        if (!$address) {
            return $this->failNotFound('Address not found');
        }

        if ($this->model->delete($uuid)) {
            return $this->respondDeleted(['status' => 'Address deleted successfully']);
        }

        return $this->failServerError('Failed to delete address');
    }
}
