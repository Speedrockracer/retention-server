<?php declare(strict_types=1);

namespace App;

use App\DataLoader;
use App\GraphDataProcessor;

final class MainController {

    // Only one call so no routing necessary
    public function run(): void {
        $dataloader = new DataLoader();
        $processor = new GraphDataProcessor($dataloader);

        $this->printJsonResponse(
            $processor->buildRetentionGraphData()
        );
    }

    private function printJsonResponse($data): void {
        header('Content-Type: application/json;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
        print(json_encode($data));
    }
}
