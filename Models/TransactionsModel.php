<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionsModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'transaction_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'owner_uuid',
        'booker_uuid',
        'car_id',
        'amount',
        'payment_method',
        'transaction_status',
        'paid_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // Custom method to get transactions by owner UUID
    public function getTransactionsByOwnerUuid($uuid)
    {
        return $this->where('owner_uuid', $uuid)->findAll();
    }

    // Custom method to get transactions by booker UUID
    public function getTransactionsByBookerUuid($uuid)
    {
        return $this->where('booker_uuid', $uuid)->findAll();
    }

    // Custom method to get transaction by ID
    public function getTransactionById($id)
    {
        return $this->find($id);
    }
}
