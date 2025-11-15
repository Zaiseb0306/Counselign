<?php namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;

class Services extends Controller
{
    public function index()
    {
        return view('services_page');
    }
}
