<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;

class Announcements extends BaseController
{
    public function index()
    {
        // You can load a view here
        return view('admin/announcements');
    }
}
