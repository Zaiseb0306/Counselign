<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use Config\Database;

class Home extends Controller
{
    public function index()
    {
        $db = Database::connect();

        if ($db->connID) {
            echo "Connected to MySQL successfully!";
        } else {
            echo "Failed to connect to MySQL.";
        }
    }
}