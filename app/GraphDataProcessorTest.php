<?php declare(strict_types=1);

namespace App;

use DateTime;
use DateInterval;
use PHPUnit\Framework\TestCase;
use function _\groupBy;
use App\GraphDataProcessor;
use App\User;

final class GraphDataProcessorTest extends TestCase {
    private $processor;

    public function __construct() {
        parent::__construct();
        $this->processor = new GraphDataProcessor();
    }

    public function testAddsUserToRetentionCorrectly(): void {
        $steps = array_flip(GraphDataProcessor::retentionNames);
        // Create empty retention data
        $lastRetention = array_fill_keys(GraphDataProcessor::retentionNames, 0);

        // create random users
        $testUsers = $this->generateTestUsers();
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
        $testUsers = $this->generateTestUsers();

        // Execute
        $retention = $this->processor->reduceUsersToRetentionData($testUsers);

        // Check that we counted all users
        $this->assertEquals(count($testUsers), $retention[
            GraphDataProcessor::retentionNames[GraphDataProcessor::TOTAL]
        ]);
    }

    public function testUserDataIsSplittedCorrectly(): void {
        $weekCount = 5;
        $userCount = 100;

        // create 100 random users
        $testUsers = $this->generateTestUsers($userCount, $weekCount);
        
        // Execute
        $weeks = $this->processor->buildRetentionGraphData($testUsers);

        // Check that all the weeks are extracted properly
        $this->assertEquals(count($weeks), $weekCount);
        
        // Check that each week has the correct total user count
        foreach($weeks as $week) {
            $this->assertEquals($userCount / $weekCount, $week['data'][
                GraphDataProcessor::retentionNames[GraphDataProcessor::TOTAL]
            ]);
        }
    }

    // Test helper functions
    private function generateTestUsers(int $count = 100, int $weeks = 5): array {
        $result = [];

        // Spread over $weeks
        $maxUsersPerWeek = $count / $weeks;

        // Ensure we only give valid data to the test.
        // We want to spread the users equally among the available weeks.
        $this->assertEquals(0, $count % $weeks); 
        
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
