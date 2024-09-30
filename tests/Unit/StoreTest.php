<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Services\StoreService; // Make sure this is the correct namespace

class StoreTest extends TestCase
{
    protected $numSales = 10;
    protected $maxStoresInDay = 30;

    protected $fileName = [
        'storeTests/stores_weekly_fail_test',
        'storeTests/stores_biweekly_fail_test',
        'storeTests/stores_monthly_fail_test',
        'storeTests/stores_success_test',
    ];

    protected function getMaximumStoresInWeek(): int
    {
        return $this->numSales * $this->maxStoresInDay * 6;
    }

    protected function assertRawResult(array $result): int
    {
        $this->assertCount(3, $result, 'The result should contain exactly 3 array.'); // no cycle other than weekly, biweekly, and monthly
        $totalSum = 0;
        foreach ($result as $array) {
            $this->assertIsArray($array, 'Each item in the result should be an array.');
            foreach ($array as $item) {
                $this->assertArrayHasKey('ranking', $item, 'Each item should have a ranking field.'); // ranked and grouped
            }
            $totalSum += count($array);
        }
        return $totalSum;
    }

    protected function assertFailRawResult(array $result, int $sumModifier = 1)
    {
        $totalSum = $this->assertRawResult($result);
        $this->assertGreaterThan($this->getMaximumStoresInWeek(), $totalSum / $sumModifier, 'The sum of all elements in the arrays should greater than 1800.'); // ensure it exceeds maximum stores can be visited in a week
    }

    public function testParseFailData()
    {
        for($i=0; $i<3; $i++) {
            $service = new StoreService();
            $result = $service->parseStoreData($this->fileName[$i].'.csv', $this->fileName[$i].'.json');
            $this->assertFailRawResult($result, pow(2, $i));
        }
    }


    public function testScheduleFailData()
    {
        for($i=0; $i<3; $i++) {
            $service = new StoreService();
            $this->expectExceptionMessage('No feasible solution found'); // ensure return error for exceeding stores limit
            $result = $service->storeSchedule($this->numSales, $this->fileName[$i].'.json');
        }
    }

    public function testParseSuccessData()
    {
        $service = new StoreService();
        $result = $service->parseStoreData($this->fileName[3].'.csv', $this->fileName[3].'.json');
        $totalSum = $this->assertRawResult($result);
        $this->assertGreaterThan($this->getMaximumStoresInWeek(), $this->getMaximumStoresInWeek() * 4, 'The sum of all elements in the arrays should be less than 1801.'); // ensure not exceeding stores limit
    }


    public function testScheduleSuccessData()
    {
        $service = new StoreService();
        $result = $service->storeSchedule($this->numSales, $this->fileName[3].'.json');
        $this->assertCount(4, $result, 'The result should contain exactly 4 weeks planning.');
        $totalStores = 0;
        foreach ($result as $array) {
            $this->assertIsArray($array, 'Each item in the result should be an array.');
            $totalStores += count($array);
        }
        $this->assertEquals($this->getMaximumStoresInWeek() * 4, $totalStores, 'The scheduled total visited should be equal to maximum visited number.'); // test case is at limit, ensure it equals to stores limt

        $result = $service->salesSchedule($result, $this->numSales);
        $this->assertCount(4, $result, 'The result should contain exactly 4 weeks planning.');
        $totalStores = 0;
        foreach ($result as $sales) {
            $this->assertIsArray($sales, 'Each item in the result should be an array.');
            $this->assertCount($this->numSales, $sales, 'The scheduled sales should be equal to number of sales.');

            foreach ($sales as $salesStoresDay) {
                $this->assertIsArray($salesStoresDay, 'Each item in the result should be an array.');
                $this->assertCount(6, $salesStoresDay, 'The scheduled day for sales should be equal to 6 working days.');

                foreach ($salesStoresDay as $stores) {
                    $this->assertIsArray($salesStoresDay, 'Each item in the result should be an array.');
                    $totalStores += count($stores);
                }
            }
        }
        $this->assertEquals($this->getMaximumStoresInWeek() * 4, $totalStores, 'The scheduled total visited should be equal to maximum visited number.');
    }
}
