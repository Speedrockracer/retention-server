<?php declare(strict_types=1);

namespace App;

use SplFileObject;
use App\User;

final class DataLoader {

    const FILE_LOCATION = __DIR__ . "/export.csv";

    public function loadUsers(): array {
        $file = new SplFileObject(DataLoader::FILE_LOCATION);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(';', '"', '\\');
    
        $result = [];
        foreach ($file as $row) {
            if(count($row) === 5) { // Ignore empty rows
                $result[] = $this->createUserFromRow($row);
            }
        }

        return $result;
    }

    private function createUserFromRow(array $row): User {
        $user = new User();

        $user->id = $row[0];
        $user->createdAt = $row[1];
        $user->onboardingPercentage = $row[2];
        $user->countApplications = $row[3];
        $user->countAcceptedApplications = $row[4];

        return $user;
    }
}
