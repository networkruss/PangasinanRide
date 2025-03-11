<?php

namespace App\Models;

use CodeIgniter\Model;

class CarListingsModel extends Model
{
    protected $table            = 'car_listings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'owner_uuid',
        'brand',
        'model',
        'year',
        'rental',
        'max_speed',
        'fuel_type',
        'seats',
        'car_image',
        'description',
        'availability',
        'status',
        'location',
        'listed_on'
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


    // Custom method to get car listings by owner UUID
    public function getListingsByOwnerUuid($uuid)
    {
        return $this->where('owner_uuid', $uuid)->findAll();
    }

    // Custom method to get a single listing by ID
    public function getListingById($id)
    {
        return $this->find($id);
    }
}
