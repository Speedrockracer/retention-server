<?php declare(strict_types=1);

namespace App;

use DateTime;
use DateInterval;
use PHPUnit\Framework\TestCase;
use function _\groupBy;
use App\GraphDataProcessor;
use App\User;
use App\DataLoaderInterface;

final class GraphDataProcessorTest extends TestCase {
    private $processor;
    private $dataloader;

    public function __construct() {
        parent::__construct();
        // Dependecies
        $this->dataloader = new TestDataLoader();
        $this->processor = new GraphDataProcessor($this->dataloader);
    }

    public function testAddsUserToRetentionCorrectly(): void {
        $steps = array_flip(GraphDataProcessor::retentionNames);
        // Create empty retention data
        $lastRetention = array_fill_keys(GraphDataProcessor::retentionNames, 0);

        // create random users
        $testUsers = $this->dataloader->loadUsers();
        foreach($testUsers as $testUser) {

            // Call the add method
            $newRetention = $this->processor->addUserToRetentionData(
                $lastRetention,
                $testUser
            );

            // Check if the numbers are added up correctly
            foreach($newRetention as $step => $userCount) {
                if (
                    intval($steps[$step]) <= intval($testUser->onboardingPercentage)
                    || $steps[$step] === GraphDataProcessor::TOTAL
                ) {
                    $this->assertEquals($userCount, $lastRetention[$step] + 1);
                } else {
                    $this->assertEquals($userCount, $lastRetention[$step]);
                }
            }

            $lastRetention = $newRetention;
        }
    }

    public function testReducesUserArrayCorrectly(): void {
        // create 100 random users
        $testUsers = $this->dataloader->loadUsers();

        // Execute
        $retention = $this->processor->reduceUsersToRetentionData($testUsers);

        // Check that we counted all users
        $this->assertEquals(count($testUsers), $retention[
            GraphDataProcessor::retentionNames[GraphDataProcessor::TOTAL]
        ]);
    }

    public function testUserDataIsSplittedCorrectly(): void {
        $weekCount = $this->dataloader->weeks;
        $userCount = $this->dataloader->count;
        // Execute
        $weeks = $this->processor->buildRetentionGraphData();

        // Check that all the weeks are extracted properly
        $this->assertEquals(count($weeks), $weekCount);
        
        // Check that each week has the correct total user count
        foreach($weeks as $week) {
            $this->assertEquals($userCount / $weekCount, $week['data'][
                GraphDataProcessor::retentionNames[GraphDataProcessor::TOTAL]
            ]);
        }
    }
}

// Randomly generates users for testing.
final class TestDataLoader implements DataLoaderInterface {
    // Loading settings
    public $count = 100;
    public $weeks = 5;

    public $users;

    public function loadUsers(): array {
        $this->users = $this->generateTestUsers($this->count, $this->weeks);
        return $this->users;
    }

    // Test helper functions
    public function generateTestUsers($count, $weeks): array {
        $result = [];

        // Spread over $weeks
        $maxUsersPerWeek = $count / $weeks;

        // Ensure we only give valid data to the test.
        // We want to spread the users equally among the available weeks.
        if ($count % $weeks != 0) throw new Exception("Unable to fit users in given weeks.");
        
        $currentWeek = new DateTime();
        $weekInterval = new DateInterval('P1W');

        for($i = 0; $i < $count; $i++) {
            if($i % $maxUsersPerWeek === 0) { // go to the next week
                $currentWeek->add($weekInterval);
            }
            $random = rand(1, count(GraphDataProcessor::retentionNames) - 1);
            $userStep = array_keys(GraphDataProcessor::retentionNames)[$random];

            $result[] = $this->createTestUser($currentWeek, $userStep);
        }

        return $result;
    }

    private function createTestUser(DateTime $createdAt, int $step = 0): User {
        $user = new User();
        $user->id = rand();
        $user->createdAt = $createdAt->format('Y-m-d');
        $user->onboardingPercentage = $step;
        $user->countApplications = 0;
        $user->countAcceptedApplications = 0;

        return $user;
    }
}
