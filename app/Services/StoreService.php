<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;

enum Cycle: string
{
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
}

class StoreService
{
    protected $maxStoresInDay = 30;
    protected $parsedDataFilename = 'stores_parsed.json';


    private function distance(string $lat1, string $lon1, string $lat2, string $lon2): float
    {   
        // Euclidean distance between two points.
        return sqrt(pow((float) $lat2 - (float) $lat1, 2) + pow((float) $lon2 - (float) $lon1, 2));
    }

    private function rankAndGroupData($stores): array
    {
        $groupedStores = [
            Cycle::WEEKLY->value => [],
            Cycle::BIWEEKLY->value => [],
            Cycle::MONTHLY->value => [],
        ];
        $visitedStores = [];
        $ranking = 0;
        $currentPoint = ["-7.9826", "112.6308"]; // HQ lat & lon (starting point)

        // Nearest Neighbor Algorithm
        // However, it may not always produce optimal solutions as it can lead to suboptimal routes in certain scenarios.
        while ($ranking < count($stores)) {

            $closestData = [PHP_INT_MAX, -1]; // distance, index
            for ($i = 0; $i < count($stores); $i++) {
                if($visitedStores[$i] ?? false) continue;

                $store = $stores[$i];
                $dist = $this->distance($currentPoint[0], $currentPoint[1], $store['latitude'], $store['longitude']);
                if($dist < $closestData[0]) {
                    $closestData = [$dist, $i];
                }
            }

            $closest = $stores[$closestData[1]];
            $groupedStores[strtolower($closest['final_cycle'])][] = $closest + ['ranking' => $ranking++];
            $currentPoint = [$closest['latitude'], $closest['longitude']];
            $visitedStores[$closestData[1]] = true;
            $closestData = [PHP_INT_MAX, -1];
        }
        return $groupedStores;
    }

    public function parseStoreData(string $fileName = 'stores.csv', ?string $parsedDataFilename = null, string $filePath = 'app/private/'): array
    {
        if (!Storage::exists($fileName)) {
            throw new \Exception('File not found');
        }
        $csvData = [];
        $handle = fopen(storage_path($filePath . $fileName), 'r');

        if ($handle !== false) {
            $header = fgetcsv($handle); // Read the first row as headers

            // Transform headers to lowercase and replace spaces/symbols with underscores
            $header = array_map(function ($key) {
                return preg_replace('/[^a-z0-9]+/', '_', strtolower($key));
            }, $header);
        
            // Read each row as an associative array
            while (($row = fgetcsv($handle)) !== false) {
                $csvData[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        $groupedData = $this->rankAndGroupData($csvData);

        // Save parsed data to .json file
        $jsonContent = json_encode($groupedData);
        Storage::put($parsedDataFilename ?? $this->parsedDataFilename, $jsonContent);

        return $this->rankAndGroupData($csvData);
    }

    public function storeSchedule(int $numSales, ?string $parsedDataFilename = null): array
    {
        $parsedDataFilename = $parsedDataFilename ?? $this->parsedDataFilename;
        if (!Storage::exists($parsedDataFilename)) {
            throw new \Exception('File not found');
        }

        $jsonContent = Storage::get($parsedDataFilename);
        $data = json_decode($jsonContent, true);
        $weekly = $data[Cycle::WEEKLY->value];
        $biweekly = $data[Cycle::BIWEEKLY->value];
        $monthly = $data[Cycle::MONTHLY->value];

        // Count needed number of stores in a week
        $storesInWeek = count($weekly) + count($biweekly)/2 + count($monthly)/4;
        $notWeeklyStoresInWeek = (int) ceil($storesInWeek - count($weekly));

        if(($numSales * $this->maxStoresInDay * 6) < $storesInWeek) {
            throw new \Exception('No feasible solution found');
        }

        // Give them remaining field to indiciate store's number of visit
        $biweekly = array_map(function ($obj) {
            $obj['remaining'] = 2;
            return $obj;
        }, $biweekly);
        $monthly = array_map(function ($obj) {
            $obj['remaining'] = 1;
            return $obj;
        }, $monthly);

        // Combile biweekly and monthly and sort to get smaller ranking value and prioritize biweekly (avoid furthest biweekly not chosen)
        $mergedArray = array_merge($biweekly, $monthly);
        usort($mergedArray, function ($a, $b) {
            if ($a['remaining'] === $b['remaining']) {
                return $a['ranking'] <=> $b['ranking']; // Sort by ranking if remaining is the same
            }
            return $b['remaining'] <=> $a['remaining']; // Sort by remaining DESC (prioritize higher freq)
        });

        $weeklyStores = [];
        for ($i = 0; $i < 4; $i++) {
            $sliced = array_slice($mergedArray, 0, $notWeeklyStoresInWeek);
            // combine with weekly data, as weekly data will always be chosen in each week
            $selected = array_merge($weekly, $sliced);
            usort($selected, function ($a, $b) {
                return $a['ranking'] <=> $b['ranking'];
            });
            $weeklyStores[] = $selected;
            for ($j = 0; $j < count($sliced); $j++) {
                $mergedArray[$j]['remaining']--; // Decrease the remaining count by 1 in the original $data array
            }
            $mergedArray = array_values(array_filter($mergedArray, function ($store) {
                return $store['remaining'] > 0;
            }));
        }
        
        return $weeklyStores;
    }

    function salesSchedule(array $storeSchedule, int $numSales): array
    {
        $result = [];
        $numDays = 6;

        foreach ($storeSchedule as $weekStores) {
            $numStores = count($weekStores);
            $base = intdiv($numStores, $numSales);
            $remainder = $numStores % $numSales;

            // sales var
            $salesSchedule = [];
            $storeIndex = 0;
            for ($i = 0; $i < $numSales; $i++) {
                // Assign extra store to the first $remainder sales reps
                $numForSalesRep = $base + ($i < $remainder ? 1 : 0);
                $salesStore = array_slice($weekStores, $storeIndex, $numForSalesRep);
                $storeIndex += $numForSalesRep;

                // Now divide those stores into 6 days for the sales rep
                $storesPerDay = [];
                $baseStoresPerDay = intdiv(count($salesStore), $numDays);
                $remainderStoresForDays = count($salesStore) % $numDays;

                $dayStartIndex = 0;
                for ($day = 0; $day < $numDays; $day++) {
                    $numForDay = $baseStoresPerDay + ($day < $remainderStoresForDays ? 1 : 0);
                    $storesPerDay[] = array_slice($salesStore, $dayStartIndex, $numForDay);
                    $dayStartIndex += $numForDay;
                }
                $salesSchedule[] = $storesPerDay;
            }
            $result[] = $salesSchedule;
        }

        return $result;
    }

}
