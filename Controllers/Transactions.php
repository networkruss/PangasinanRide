<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\NotificationsModel;

class Transactions extends ResourceController
{
    protected $modelName = 'App\Models\TransactionsModel';
    protected $format    = 'json';

    // Get all transactions
    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    // Get a specific transaction by ID
    public function show($id = null)
    {
        $transaction = $this->model->getTransactionById($id);

        if (!$transaction) {
            return $this->failNotFound('Transaction not found');
        }

        return $this->respond($transaction);
    }

    // Get transactions by owner UUID
    public function showByOwnerUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('Owner UUID is required');
        }

        $transactions = $this->model->getTransactionsByOwnerUuid($uuid);

        if (!$transactions) {
            return $this->failNotFound('No transactions found for this owner');
        }

        return $this->respond($transactions);
    }

    // Get transactions by booker UUID
    public function showByBookerUUID($uuid = null)
    {
        if (!$uuid) {
            return $this->failValidationErrors('Booker UUID is required');
        }

        $transactions = $this->model->getTransactionsByBookerUuid($uuid);

        if (!$transactions) {
            return $this->failNotFound('No transactions found for this booker');
        }

        return $this->respond($transactions);
    }

    // Create or update a transaction
    public function saveTransaction()
    {
        $data = $this->request->getPost();
        $id = $data['transaction_id'] ?? null;
        $notificationModel = new NotificationsModel();

        // Insert or update based on transaction_id
        if ($id) {
            // Update existing transaction
            if ($this->model->update($id, $data)) {
                $transaction = $this->model->find($id);

                // Notify the booker if payment status is updated to 'paid'
                if ($data['transaction_status'] === 'paid') {
                    $notificationModel->insert([
                        'uuid' => $transaction['booker_uuid'],
                        'title' => 'Payment Successful',
                        'content' => 'Your payment for the booking has been successfully processed.',
                    ]);

                    // Notify the owner that the payment is completed
                    $notificationModel->insert([
                        'uuid' => $transaction['owner_uuid'],
                        'title' => 'Payment Received',
                        'content' => 'A payment has been received for your car listing.',
                    ]);
                }

                // Notify the booker if payment status is updated to 'failed'
                if ($data['transaction_status'] === 'failed') {
                    $notificationModel->insert([
                        'uuid' => $transaction['booker_uuid'],
                        'title' => 'Payment Failed',
                        'content' => 'Your payment for the booking has failed. Please try again.',
                    ]);
                }

                return $this->respond(['status' => 'Transaction updated successfully']);
            }
        } else {
            // Create new transaction
            if ($this->model->insert($data)) {
                return $this->respondCreated(['status' => 'Transaction created successfully']);
            }
        }

        return $this->failValidationErrors($this->model->errors());
    }

    // Delete transaction by ID
    public function delete($id = null)
    {
        $transaction = $this->model->getTransactionById($id);

        if (!$transaction) {
            return $this->failNotFound('Transaction not found');
        }

        // Proceed to delete the transaction
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['status' => 'Transaction deleted successfully']);
        }

        return $this->failServerError('Failed to delete transaction');
    }


    // Refund a transaction by ID
    public function refund($id = null)
    {
        $transaction = $this->model->getTransactionById($id);

        if (!$transaction) {
            return $this->failNotFound('Transaction not found');
        }

        // Check if the transaction is refundable (i.e., it must have been 'paid')
        if ($transaction['transaction_status'] !== 'paid') {
            return $this->failValidationErrors('Only paid transactions can be refunded');
        }

        // Update the transaction status to 'refunded' and set the paid_at timestamp to null
        $data = [
            'transaction_status' => 'refunded',
            'paid_at' => null
        ];

        if ($this->model->update($id, $data)) {
            // Notify the booker about the refund
            $notificationModel = new NotificationsModel();
            $notificationModel->insert([
                'uuid' => $transaction['booker_uuid'],
                'title' => 'Refund Processed',
                'content' => 'Your payment has been refunded successfully.',
            ]);

            // Notify the owner about the refund
            $notificationModel->insert([
                'uuid' => $transaction['owner_uuid'],
                'title' => 'Refund Issued',
                'content' => 'A refund has been issued for a transaction.',
            ]);

            return $this->respond(['status' => 'Transaction refunded successfully']);
        }

        return $this->failServerError('Failed to refund transaction');
    }
}
