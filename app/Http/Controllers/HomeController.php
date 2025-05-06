<?php

namespace App\Http\Controllers;
use App\Services\BasService;
use BAS;
use JsonException;

class HomeController extends Controller
{

    private $basService;
    public function __construct(BasService $basService)
    {

        $this->basService = $basService;
    }

    public function index()
    {
//        $ahmed = BAS::generateFetchAuthCodeJS();
//        dd($ahmed);


    }
    public function initiateTransaction()
    {

        $orderId = rand(100000, 999999);
        $amount = rand(100, 999);
        $currency = 'YER';
        return  $this->basService->initiateTransaction($amount,$currency,$orderId);


    }

    /**
     * @throws JsonException
     */
    public function order()
    {
        $orderId = rand(100000, 999999);
        $amount = rand(100, 999);
        $currency = 'YER';
        return  $this->basService->initiateTransaction($amount,$currency,$orderId);



    }
}
