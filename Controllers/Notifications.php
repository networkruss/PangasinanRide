<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Notifications extends ResourceController
{
    protected $modelName = 'App\Models\NotificationsModel';
    protected $format    = 'json';

    // Get all notifications by user UUID
    public function showByUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('UUID is required');
        }

        // Fetch notifications for the provided UUID
        $notifications = $this->model->where('uuid', $uuid)->orderBy('notified_at', 'DESC')->findAll();

        if (!$notifications) {
            return $this->failNotFound('No notifications found for this user');
        }

        return $this->respond($notifications);
    }

    public function markAsRead($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('Notification ID is required');
        }

        // Fetch the notification
        $notification = $this->model->find($id);

        if (!$notification) {
            return $this->failNotFound('Notification not found');
        }

        // Mark the notification as read
        if ($this->model->update($id, ['read' => true])) {
            return $this->respond(['status' => 'Notification marked as read']);
        }

        return $this->failServerError('Failed to mark notification as read');
    }
}
