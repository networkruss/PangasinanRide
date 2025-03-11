<?php

namespace App\Controllers;

use App\Models\FeedbackModel;
use App\Models\NotificationsModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class FeedbackController extends ResourceController
{
    protected $modelName = FeedbackModel::class;
    protected $format    = 'json';

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index()
    {
        $feedbacks = $this->model->getAll();
        return $this->respond($feedbacks);
    }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        $feedback = $this->model->find($id);
        if (!$feedback) {
            return $this->failNotFound('Feedback not found');
        }
        return $this->respond($feedback);
    }


    public function getAllFeedbackByCarId($id = null)
    {
        $feedbacks = $this->model->getFeedbackByCarId($id);
        return $this->respond($feedbacks);
    }

    public function alreadyFeedback()
    {
        $data = $this->request->getGet();

        // Ensure both 'booker_uuid' and 'car_id' are present in the request
        if (!isset($data['booker_uuid']) || !isset($data['car_id'])) {
            return $this->failValidationErrors('Missing booker_uuid or car_id');
        }

        $uuid = $data['booker_uuid'];
        $carId = $data['car_id'];

        // Check if feedback already exists
        $feedback = $this->model->alreadyFeedback($uuid, $carId);

        if ($feedback) {
            return $this->respond(['message' => 'Feedback already exists'], 200);
        } else {
            return $this->respond(['message' => 'No feedback found'], 200);
        }
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $data = $this->request->getPost();
        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        // Notify the car owner about the new feedback
        $notificationModel = new NotificationsModel();
        $notificationModel->insert([
            'uuid' => $data['owner_uuid'], // Assuming owner_uuid is part of the feedback data
            'title' => 'New Feedback Received',
            'content' => 'You have received new feedback for your car listing.',
        ]);

        return $this->respondCreated($data, 'Feedback created');
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function edit($id = null)
    {
        $feedback = $this->model->find($id);
        if (!$feedback) {
            return $this->failNotFound('Feedback not found');
        }
        return $this->respond($feedback);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        $data = $this->request->getRawInput();
        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        return $this->respond($data, 200, 'Feedback updated');
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        $feedback = $this->model->find($id);
        if (!$feedback) {
            return $this->failNotFound('Feedback not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted($feedback, 'Feedback deleted');
    }
}
