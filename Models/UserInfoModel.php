<?php

namespace App\Models;

use CodeIgniter\Model;

class UserInfoModel extends Model
{
    protected $table            = 'user_info_tbl';
    protected $primaryKey       = 'uuid';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'first_name',
        'last_name',
        'gender',
        'phone',
        'avatar',
        'driver_license',
        'driver_license_expiry',
        'vehicle_plate_number',
        'ownership'
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

    // Custom method to get user info by UUID
    public function getUserInfoByUuid($uuid)
    {
        return $this->where('uuid', $uuid)->first();
    }

}
