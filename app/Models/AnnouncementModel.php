<?php
namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'id'; // Change if your PK is different
    protected $allowedFields = ['title', 'content', 'created_at']; // Add all your columns here
    public $timestamps = false; // Set to true if you use created_at/updated_at automatically
}
