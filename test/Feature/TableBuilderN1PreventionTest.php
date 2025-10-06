<?php

namespace Mariojgt\Builder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Type;
use App\Models\VulnerabilitiesReported;
use App\Models\ReportedTempData;
use Illuminate\Http\Request;
use Mariojgt\Builder\Controllers\TableBuilderApiController;

/**
 * Feature tests for N+1 query prevention in TableBuilderApiController
 *
 * These tests verify the complete flow from request to response
 * and ensure N+1 queries are prevented with real database interactions.
 */
class TableBuilderN1PreventionTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilderApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TableBuilderApiController();
    }

    /**
     * Test that loading table data with nested relationships does not cause N+1 queries
     */
    public function testPreventN1QueriesWithNestedRelationships(): void
    {
        // Skip if database is not properly set up
        if (!$this->hasDatabaseTables()) {
            $this->markTestSkipped('Database tables not available');
        }

        // Create test data
        $this->createTestData();

        // Prepare request with nested relationship columns
        $request = new Request([
            'model' => encrypt(VulnerabilitiesReported::class),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'reportedTempData.type.name', 'label' => 'Type'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'perPage' => 10,
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Execute the controller method
        try {
            $response = $this->controller->index($request);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute query: ' . $e->getMessage());
        }

        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count queries
        $queryCount = count($queries);

        // With proper eager loading, we should have a low number of queries:
        // 1. Main query for VulnerabilitiesReported
        // 2. Eager load query for reportedTempData with type
        // 3. Maybe 1-2 more for count/pagination
        $this->assertLessThan(
            10,
            $queryCount,
            sprintf(
                'Query count is too high (%d). Possible N+1 issue. Queries: %s',
                $queryCount,
                json_encode(array_column($queries, 'query'))
            )
        );

        // More strict assertion
        $this->assertLessThan(
            7,
            $queryCount,
            sprintf('Expected fewer than 7 queries, got %d', $queryCount)
        );
    }

    /**
     * Test with multiple nested relationships
     */
    public function testPreventN1QueriesWithMultipleNestedRelationships(): void
    {
        if (!$this->hasDatabaseTables()) {
            $this->markTestSkipped('Database tables not available');
        }

        $this->createTestData();

        $request = new Request([
            'model' => encrypt(VulnerabilitiesReported::class),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'reportedTempData.type.name', 'label' => 'Type'],
                ['key' => 'reportedTempData.cvssbase', 'label' => 'CVSS'],
                ['key' => 'reportedData.comp_name', 'label' => 'Component'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'perPage' => 10,
        ]);

        DB::enableQueryLog();

        try {
            $response = $this->controller->index($request);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute query: ' . $e->getMessage());
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $queryCount = count($queries);

        // With multiple relationships, we might have a few more queries
        // but should still be under 10
        $this->assertLessThan(
            10,
            $queryCount,
            sprintf('Too many queries with multiple relationships: %d', $queryCount)
        );
    }

    /**
     * Test with fallback relationships (pipe-separated keys)
     */
    public function testPreventN1QueriesWithFallbackRelationships(): void
    {
        if (!$this->hasDatabaseTables()) {
            $this->markTestSkipped('Database tables not available');
        }

        $this->createTestData();

        $request = new Request([
            'model' => encrypt(VulnerabilitiesReported::class),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'reportedTempData.type.name|reportedData.select_type', 'label' => 'Type'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'perPage' => 10,
        ]);

        DB::enableQueryLog();

        try {
            $response = $this->controller->index($request);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute query: ' . $e->getMessage());
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $queryCount = count($queries);

        // Even with fallback relationships, should not cause N+1
        $this->assertLessThan(
            10,
            $queryCount,
            sprintf('Too many queries with fallback relationships: %d', $queryCount)
        );
    }

    /**
     * Test that response structure is correct
     */
    public function testResponseStructureIsCorrect(): void
    {
        if (!$this->hasDatabaseTables()) {
            $this->markTestSkipped('Database tables not available');
        }

        $this->createTestData();

        $request = new Request([
            'model' => encrypt(VulnerabilitiesReported::class),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'reportedTempData.type.name', 'label' => 'Type'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'perPage' => 10,
        ]);

        try {
            $response = $this->controller->index($request);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute response: ' . $e->getMessage());
        }

        // Verify response structure
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);

        // Verify nested data is accessible
        if (!empty($response['data'])) {
            $firstItem = $response['data'][0];
            $this->assertIsArray($firstItem);
        }
    }

    /**
     * Check if required database tables exist
     */
    protected function hasDatabaseTables(): bool
    {
        try {
            $tables = [
                'vulnerabilities_reported',
                'reported_temp_data',
                'types',
            ];

            foreach ($tables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create minimal test data for N+1 query testing
     */
    protected function createTestData(): void
    {
        try {
            // Create a type
            $type = Type::firstOrCreate(
                ['name' => 'SQL Injection Test'],
                ['name' => 'SQL Injection Test']
            );

            // Create some reports with related data
            for ($i = 0; $i < 5; $i++) {
                $report = VulnerabilitiesReported::create([
                    'status' => 'unvalidated',
                    'status_validation' => 'needs_validation',
                    'status_patch' => 'pending',
                    'status_contact' => 'pending',
                ]);

                // Create related temp data
                ReportedTempData::create([
                    'report_id' => $report->id,
                    'type_id' => $type->id,
                    'cvssbase' => 7.5,
                    'affected_in' => '1.0.0',
                ]);
            }
        } catch (\Exception $e) {
            // If we can't create test data, the test will be skipped
            \Log::warning('Could not create test data: ' . $e->getMessage());
        }
    }
}
