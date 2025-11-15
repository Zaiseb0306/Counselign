<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }
}
