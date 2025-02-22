<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\User;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Http;

class HistoryController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', ['except'=>['login','register']]);
    }
}
