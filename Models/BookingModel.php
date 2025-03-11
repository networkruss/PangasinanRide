<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table            = 'booking';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'owner_uuid',
        'booker_uuid',
        'car_id',
        'status',
        'amount',
        'pickup_date',
        'dropoff_date',
        'pickup_location',
        'dropoff_location',
        'booked_at'
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

    // Custom method to get bookings by owner UUID
    public function getBookingsByOwnerUuid($uuid)
    {
        return $this->where('owner_uuid', $uuid)->findAll();
    }

    // Custom method to get bookings by booker UUID
    public function getBookingsByBookerUuid($uuid)
    {
        return $this->where('booker_uuid', $uuid)->findAll();
    }

    // Custom method to get booking by ID
    public function getBookingById($id)
    {
        return $this->where('id', $id)->first();
    }
}
