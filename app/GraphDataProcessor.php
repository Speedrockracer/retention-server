<?php declare(strict_types=1);
namespace App;

use DateTime;
use function _\{reduce, groupBy, map};
use App\User;
use App\DataLoaderInterface;

final class GraphDataProcessor {

    const TOTAL = "total";
    const STEP_CREATE_ACCOUNT = 0;
    const STEP_ACTIVATE_ACCOUNT = 20;
    const STEP_PROFILE_INFO = 40;
    const STEP_JOBS_INTERESTED = 50;
    const STEP_EXPERIENCE = 70;
    const STEP_FREELANCER = 90;
    const STEP_AWAITING_APPROVAL = 99;
    const STEP_APPROVAL = 100;

    const retentionNames = [
        self::TOTAL => "Total",
        self::STEP_CREATE_ACCOUNT => "create account",
        self::STEP_ACTIVATE_ACCOUNT => "activate account",
        self::STEP_PROFILE_INFO => "provide profile information",
        self::STEP_JOBS_INTERESTED => "what jobs are you interested in?",
        self::STEP_EXPERIENCE => "do you have relevant experience in these jobs?",
        self::STEP_FREELANCER => "are you a freelancer?",
        self::STEP_AWAITING_APPROVAL => "waiting for approval",
        self::STEP_APPROVAL => "approval",
    ];

    private $initialRetention = [];
    private $dataloader;

    public function __construct(DataLoaderInterface $dataloader) {
        $this->dataloader = $dataloader;
        $this->initialRetention = array_fill_keys(self::retentionNames, 0);
    }

    public function buildRetentionGraphData(): array {
        return $this->createRetentionDataForWeeks(
            $this->groupByWeek($this->dataloader->loadUsers())
        );
    }

    // Group user data per weekly cohort
    public function groupByWeek(array $data): array {
        return groupBy($data, function(User $value) {
            $date = new DateTime($value->createdAt);
            return $date->format("W");
        });
    }

    // Transform the value of each week to retention data.
    public function createRetentionDataForWeeks(array $weeks): array {
        return reduce($weeks, function(array $currentValues, array $users, int $key) {
            $result = $currentValues;

            $result[] = [
                'number' => $key,
                'title' => "Week ".$key,
                'data' => $this->reduceUsersToRetentionData($users)
            ];
            return $result;
        }, []);
    }

    // Reduce and array of users to retention data
    public function reduceUsersToRetentionData(array $users): array {
        return reduce(
            $users,
            [$this, "addUserToRetentionData"],
            $this->initialRetention
        );
    }

    // Add data of a single user to a retention data array
    public function addUserToRetentionData(array $retention, User $user): array {
        return reduce(
            self::retentionNames, 
            function($result, $step, $stepPercentage) use ($retention, $user) {
                if(
                    intval($user->onboardingPercentage) >= $stepPercentage 
                    || $stepPercentage === self::TOTAL
                ) { // Add 1 to the step if the user completed the step
                    $result[$step] = $result[$step] + 1;
                }
                return $result;
            },
            $retention
        );
    }
}
