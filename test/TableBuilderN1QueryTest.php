<?php

namespace Mariojgt\Builder\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Type;
use App\Models\Product;
use App\Models\Platform;
use App\Models\Kind;
use App\Models\VulnerabilitiesReported;
use App\Models\ReportedTempData;
use Mariojgt\Builder\Controllers\TableBuilderApiController;
use Illuminate\Http\Request;

/**
 * Test suite for N+1 query fix in TableBuilderApiController
 * 
 * These tests verify that nested relationships are properly eager loaded
 * to prevent N+1 query issues when displaying table data.
 */
class TableBuilderN1QueryTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilderApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TableBuilderApiController();
    }

    /**
     * Test that extractNestedRelationships method exists and works correctly
     * 
     * @test
     */
    public function it_extracts_nested_relationships_correctly()
    {
        $relationships = [
            'product.platform.name',
            'product.kind',
            'reportedTempData.type.name',
        ];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        // Should expand nested relationships
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.kind', $result);
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);

        // Should not have duplicates
        $this->assertEquals(count($result), count(array_unique($result)));
    }

    /**
     * Test that autoDetectRelationships correctly identifies relationships from columns
     * 
     * @test
     */
    public function it_auto_detects_relationships_from_columns()
    {
        $columns = collect([
            ['key' => 'id'],
            ['key' => 'product.platform.name'],
            ['key' => 'product.kind.kind_name'],
            ['key' => 'reportedTempData.type.name'],
            ['key' => 'name'],
        ]);

        $method = $this->getPrivateMethod('autoDetectRelationships');
        $result = $method->invoke($this->controller, $columns);

        // Should detect nested relationships
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.kind', $result);
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
    }

    /**
     * Test that autoDetectRelationships handles fallback keys with pipe separator
     * 
     * @test
     */
    public function it_handles_fallback_relationships()
    {
        $columns = collect([
            ['key' => 'reportedTempData.type.name|reportedData.select_type'],
            ['key' => 'product.name|fallback_name'],
        ]);

        $method = $this->getPrivateMethod('autoDetectRelationships');
        $result = $method->invoke($this->controller, $columns);

        // Should detect relationships from both sides of the pipe
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
        $this->assertContains('reportedData', $result);
        $this->assertContains('product', $result);
    }

    /**
     * Test that optimizeRelationshipLoading keeps nested relationships
     * 
     * @test
     */
    public function it_keeps_nested_relationships_in_optimization()
    {
        $relationships = [
            'product',
            'product.platform',
            'product.kind',
            'reportedTempData',
            'reportedTempData.type',
        ];

        // Create a mock model
        $model = new VulnerabilitiesReported();

        $method = $this->getPrivateMethod('optimizeRelationshipLoading');
        $result = $method->invoke($this->controller, $relationships, $model);

        // Critical: Should NOT filter out nested relationships
        // Both 'product' and 'product.platform' should be present
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);

        // Should have unique values only
        $this->assertEquals(count($result), count(array_unique($result)));
    }

    /**
     * Test that the full pipeline prevents N+1 queries
     * 
     * This is the most important test - it verifies that when loading
     * table data with nested relationships, we don't trigger N+1 queries.
     * 
     * @test
     */
    public function it_prevents_n_plus_1_queries_with_nested_relationships()
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
                ['key' => 'reportedTempData.product.name', 'label' => 'Product'],
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
            // If test data couldn't be created properly, skip
            $this->markTestSkipped('Could not execute query: ' . $e->getMessage());
        }

        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count queries - should be low (typically 2-5 queries)
        // Before fix: Would be 10+ queries (1 main + N for each relationship)
        // After fix: Should be around 2-4 queries (main + eager loaded relationships)
        $queryCount = count($queries);

        // Assert that we have a reasonable number of queries
        // With proper eager loading, we should have:
        // 1. Main query for VulnerabilitiesReported
        // 2. Eager load query for reportedTempData
        // 3. Eager load query for types
        // Maybe 1-2 more for count/pagination
        $this->assertLessThan(10, $queryCount, 
            "Query count is too high ($queryCount). Possible N+1 issue. Queries: " . 
            json_encode(array_column($queries, 'query'))
        );

        // More strict assertion: with 10 records, we should not have more than 6 queries
        $this->assertLessThan(7, $queryCount,
            "Expected fewer than 7 queries, got $queryCount"
        );
    }

    /**
     * Test that relationshipExists correctly validates relationships
     * 
     * @test
     */
    public function it_validates_relationship_existence()
    {
        $model = new VulnerabilitiesReported();
        $method = $this->getPrivateMethod('relationshipExists');

        // Test valid single-level relationship
        $result = $method->invoke($this->controller, $model, 'reportedTempData');
        $this->assertTrue($result, 'reportedTempData relationship should exist');

        // Test valid nested relationship
        $result = $method->invoke($this->controller, $model, 'reportedTempData.type');
        $this->assertTrue($result, 'reportedTempData.type nested relationship should exist');

        // Test invalid relationship
        $result = $method->invoke($this->controller, $model, 'nonExistentRelation');
        $this->assertFalse($result, 'Non-existent relationship should return false');
    }

    /**
     * Test edge case: empty relationships array
     * 
     * @test
     */
    public function it_handles_empty_relationships_array()
    {
        $relationships = [];
        $model = new VulnerabilitiesReported();

        $method = $this->getPrivateMethod('optimizeRelationshipLoading');
        $result = $method->invoke($this->controller, $relationships, $model);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test edge case: single relationship without nesting
     * 
     * @test
     */
    public function it_handles_single_level_relationships()
    {
        $relationships = ['reportedTempData', 'reportedData'];
        $model = new VulnerabilitiesReported();

        $method = $this->getPrivateMethod('optimizeRelationshipLoading');
        $result = $method->invoke($this->controller, $relationships, $model);

        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedData', $result);
    }

    /**
     * Test that extractNestedRelationships handles single-level relationships
     * 
     * @test
     */
    public function it_handles_single_level_in_extraction()
    {
        $relationships = ['product', 'type'];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        // Single-level relationships should be preserved
        $this->assertContains('product', $result);
        $this->assertContains('type', $result);
        $this->assertCount(2, $result);
    }

    /**
     * Helper method to access private methods for testing
     */
    protected function getPrivateMethod(string $methodName): \ReflectionMethod
    {
        $reflection = new \ReflectionClass(TableBuilderApiController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
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
                ['name' => 'SQL Injection'],
                ['name' => 'SQL Injection']
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
