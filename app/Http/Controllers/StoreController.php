<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StoreService;

class StoreController extends Controller
{
    //
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function parseStoreData() {
        try {
            $data = $this->storeService->parseStoreData();
            return response()->json($data, 200, ['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
        } catch(\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing the store data: ' . $e->getMessage()], 500);
        }
    }

    public function schedule() {
        try {
            $numSales = 10;
            $storeSchedule = $this->storeService->storeSchedule($numSales);
            $salesSchedule = $this->storeService->salesSchedule($storeSchedule, $numSales);

            return view('calendar', compact('salesSchedule'));
        } catch(\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing the store data: ' . $e->getMessage()], 500);
        }
    }
}
