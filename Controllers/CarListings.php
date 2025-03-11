<?php

namespace App\Controllers;

use App\Models\NotificationsModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class CarListings extends ResourceController
{
    protected $modelName = 'App\Models\CarListingsModel';
    protected $format    = 'json';

    // Get all car listings
    public function index()
    {
        // Retrieve all car listings
        $carListings = $this->model->findAll();

        // Check if there are any car listings
        if (!$carListings) {
            return $this->failNotFound('No car listings found');
        }

        // Initialize the UserInfoModel and other related models for fetching owner information
        $ownerModel = new \App\Models\UserInfoModel();
        $ownerAddress = new \App\Models\UserAddressModel();
        $ownerEmail = new \App\Models\UserAuthModel();
        $feedbackModel = new \App\Models\FeedbackModel(); // Load the FeedbackModel

        // Iterate over each car listing and append the owner information
        foreach ($carListings as &$listing) {
            // Fetch owner info by owner_uuid
            $owner = $ownerModel->getUserInfoByUuid($listing['owner_uuid']);

            // Check if owner information exists
            if ($owner) {
                $owner_email = $ownerEmail->getUserByUuid($owner['uuid']);
                $address = $ownerAddress->getAddressByUuid($owner['uuid']);

                // Append owner info if it exists
                $listing['owner_info'] = $owner;

                // Append address if it exists, else set it to null
                $listing['address'] = $address ?: null;

                // Append email and verified status if user exists
                $listing['email'] = $owner_email['email'] ?? null;
                $listing['verified'] = $owner_email['verified'] ?? null;
            } else {
                // If no owner is found, set these fields to null
                $listing['owner_info'] = null;
                $listing['address'] = null;
                $listing['email'] = null;
                $listing['verified'] = null;
            }

            // Fetch feedbacks for the car listing and calculate the average rating
            $feedbacks = $feedbackModel->getFeedbackByCarId($listing['id']);
            $totalRating = array_reduce($feedbacks, function ($sum, $feedback) {
                return $sum + $feedback['rating'];
            }, 0);

            // Calculate average rating, rounded to one decimal
            $averageRating = count($feedbacks) > 0 ? round($totalRating / count($feedbacks), 1) : 0;

            // Append feedbacks and average rating to the listing
            $listing['feedbacks'] = $feedbacks;
            $listing['average_rating'] = $averageRating;
        }

        // Return the car listings with owner information and average rating
        return $this->respond($carListings);
    }




    // Get a specific car listing by ID
    public function show($id = null)
    {
        $listing = $this->model->getListingById($id);
        // Initialize the UserAuthModel for fetching owner information
        $ownerModel = new \App\Models\UserInfoModel();
        $ownerAddress = new \App\Models\UserAddressModel();
        $ownerEmail = new \App\Models\UserAuthModel();
        // Iterate over each car listing and append the owner information
        $owner = $ownerModel->getUserInfoByUuid($listing['owner_uuid']);
        $owner_email = $ownerEmail->getUserByUuid($owner['uuid']);

        // Check if the owner exists and append the data to the listing
        if ($owner) {
            $listing['owner_info'] = $ownerModel->getUserInfoByUuid($owner['uuid']);
            $listing['address'] = $ownerAddress->getAddressByUuid($owner['uuid']);
            $listing['email'] = $owner_email['email'];
            $listing['verified'] = $owner_email['verified'];
        } else {
            $listing['owner_info'] = null; // If no owner is found
            $listing['address'] = null;
        }
        if (!$listing) {
            return $this->failNotFound('Car listing not found');
        }

        return $this->respond($listing);
    }

    // Get all car listings by owner's UUID
    public function showByUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('UUID is required');
        }

        $listings = $this->model->getListingsByOwnerUuid($uuid);

        if (!$listings) {
            return $this->failNotFound('No car listings found for this owner');
        }

        return $this->respond($listings);
    }

    // Create a new car listing
    public function saveListing()
    {
        $data = $this->request->getPost();
        $files = $this->request->getFileMultiple('car_image'); // Use getFileMultiple

        $imagePaths = [];

        if ($files) {
            foreach ($files as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName();
                    $file->move(FCPATH . 'uploads/cars', $newName);
                    $imagePaths[] = 'uploads/cars/' . $newName;
                }
            }
        }

        // Insert the file paths into the data
        if (!empty($imagePaths)) {
            $data['car_image'] = implode(',', $imagePaths);
        }

        // Save the listing
        if ($this->model->insert($data)) {
            return $this->respondCreated(['status' => 'Car listing created successfully']);
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Update an existing car listing by ID and Owner UUID
    public function updateListing()
    {
        $id = $this->request->getVar('id');
        $uuid = $this->request->getVar('owner_uuid');

        if (!$id || !$uuid) {
            return $this->failValidationErrors('Both ID and Owner UUID are required');
        }

        // Get existing listing
        $listing = $this->model->getListingById($id);

        if (!$listing || $listing['owner_uuid'] !== $uuid) {
            return $this->failNotFound('Car listing not found or access denied');
        }

        $data = $this->request->getPost();

        // Handle multiple car image uploads
        $files = $this->request->getFiles();
        $imagePaths = [];

        // Process remaining existing images
        $existingImages = $this->request->getVar('existing_images');
        $existingImageArray = !empty($existingImages) ? explode(',', $existingImages) : [];

        // Handle removed images
        $removedImages = $this->request->getVar('removed_images');
        if (!empty($removedImages)) {
            $removedImagesArray = explode(',', $removedImages);
            foreach ($removedImagesArray as $imageToRemove) {
                if (file_exists(FCPATH . $imageToRemove)) {
                    unlink(FCPATH . $imageToRemove); // Remove image from filesystem
                }
            }
        }

        // Append existing images (if any) to the new image paths
        $imagePaths = array_merge($imagePaths, $existingImageArray);

        // Handle new image uploads
        if (isset($files['car_image'])) {
            $carImages = $files['car_image'];

            if (is_array($carImages)) {
                foreach ($carImages as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        // Validate the file
                        $validated = $this->validate([
                            'car_image' => [
                                'uploaded[car_image]',
                                'max_size[car_image,51200]', // 50MB max
                            ],
                        ]);

                        if ($validated) {
                            // Generate a new file name to avoid conflicts
                            $newName = $file->getRandomName();
                            $file->move(FCPATH . 'uploads/cars', $newName);
                            $imagePaths[] = 'uploads/cars/' . $newName;
                        } else {
                            return $this->fail($this->validator->getErrors());
                        }
                    }
                }
            } elseif ($carImages->isValid() && !$carImages->hasMoved()) {
                // Handle single file upload
                $validated = $this->validate([
                    'car_image' => [
                        'uploaded[car_image]',
                        'max_size[car_image,51200]', // 50MB max
                    ],
                ]);

                if ($validated) {
                    $newName = $carImages->getRandomName();
                    $carImages->move(FCPATH . 'uploads/cars', $newName);
                    $imagePaths[] = 'uploads/cars/' . $newName;
                } else {
                    return $this->fail($this->validator->getErrors());
                }
            }
        }

        // Convert the array of image paths to a comma-separated string
        if (!empty($imagePaths)) {
            $data['car_image'] = implode(',', $imagePaths);
        }

        // Update the existing listing
        if ($this->model->update($id, $data)) {
            return $this->respond(['status' => 'Car listing updated successfully']);
        }

        return $this->failValidationErrors($this->model->errors());
    }



    // Delete car listing by ID
    public function delete($id = null)
    {
        $listing = $this->model->getListingById($id);

        if (!$listing) {
            return $this->failNotFound('Car listing not found');
        }

        // Delete the car image if it exists
        if (!empty($listing['car_image']) && file_exists(FCPATH . $listing['car_image'])) {
            unlink(FCPATH . $listing['car_image']);
        }

        // Proceed to delete the listing
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['status' => 'Car listing deleted successfully']);
        }

        return $this->failServerError('Failed to delete car listing');
    }


    // Approve a car listing by ID
    public function approveListing($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Car listing ID is required');
        }

        // Fetch the car listing by ID
        $listing = $this->model->getListingById($id);

        if (!$listing) {
            return $this->failNotFound('Car listing not found');
        }

        // Update the listing status to 'approved'
        $this->model->update($id, ['status' => 'approved']);

        // Notify the owner that the listing has been approved
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $listing['owner_uuid'],
            'title' => 'Listing Approved',
            'content' => 'Your car listing has been approved and is now live.',
        ]);

        return $this->respond(['status' => 'Car listing approved successfully']);
    }

    // Decline a car listing by ID
    public function declineListing($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Car listing ID is required');
        }

        // Fetch the car listing by ID
        $listing = $this->model->getListingById($id);

        if (!$listing) {
            return $this->failNotFound('Car listing not found');
        }

        // Update the listing status to 'declined'
        $this->model->update($id, ['status' => 'declined']);

        return $this->respond(['status' => 'Car listing declined successfully']);
    }
}
