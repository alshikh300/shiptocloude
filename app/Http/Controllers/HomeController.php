<?php

namespace App\Http\Controllers;
use BAS;
use JsonException;

class HomeController extends Controller
{
    public function index()
    {
//        $ahmed = BAS::generateFetchAuthCodeJS();
//        dd($ahmed);


    }

    /**
     * @throws JsonException
     */
    public function order()
    {
        $orderId = rand(100000, 999999);
        $amount = rand(100, 999);
        $currency = 'YER';
       return BAS::initiateTransaction($orderId, $amount, $currency);



    }
}
