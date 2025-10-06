<?php

namespace Mariojgt\Builder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mariojgt\Builder\Controllers\TableBuilderApiController;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Collection;

/**
 * Comprehensive tests for N+1 query prevention in TableBuilder
 *
 * This test suite verifies that the fixes for N+1 queries are working correctly
 * by testing all the methods involved in relationship detection and optimization.
 */
class N1QueryPreventionTest extends TestCase
{
    protected TableBuilderApiController $controller;
    protected ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TableBuilderApiController();
        $this->reflection = new ReflectionClass(TableBuilderApiController::class);
    }

    /**
     * Test Case 1: Nested relationship expansion
     * This is the core fix - ensuring nested relationships are properly expanded
     */
    public function testNestedRelationshipsAreExpanded(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        // Input: Column like 'product.platform.name'
        $input = ['product.platform'];

        $result = $method->invoke($this->controller, $input);

        // CRITICAL: Both levels must be present to prevent N+1
        $this->assertContains('product', $result, 'Parent relationship must be included');
        $this->assertContains('product.platform', $result, 'Nested relationship must be included');

        // Verify no N+1 scenario
        $this->assertCount(
            2,
            $result,
            'Should have exactly 2 relationships: parent and nested'
        );
    }

    /**
     * Test Case 2: Deep nesting (3+ levels)
     * Example: product.platform.company.name
     */
    public function testDeepNestedRelationshipsAreFullyExpanded(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        $input = ['product.platform.company'];

        $result = $method->invoke($this->controller, $input);

        // All levels must be present
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.platform.company', $result);
        $this->assertCount(3, $result);
    }

    /**
     * Test Case 3: Multiple independent nested relationships
     */
    public function testMultipleNestedRelationshipsAreAllExpanded(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        $input = [
            'product.platform',
            'reportedTempData.type',
            'user.team',
        ];

        $result = $method->invoke($this->controller, $input);

        // All parent and nested relationships must be present
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
        $this->assertContains('user', $result);
        $this->assertContains('user.team', $result);

        // Total: 6 unique relationships
        $this->assertCount(6, $result);
    }

    /**
     * Test Case 4: Column configuration parsing
     * This tests the real-world scenario from FormHelper
     */
    public function testRealWorldColumnConfiguration(): void
    {
        $method = $this->getMethod('autoDetectRelationships');

        // Simulate actual FormHelper column configuration
        $columns = collect([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'product.platform.name', 'label' => 'Platform'],
            ['key' => 'product.kind.kind_name', 'label' => 'Kind'],
            ['key' => 'reportedTempData.type.name', 'label' => 'Type'],
            ['key' => 'status', 'label' => 'Status'],
        ]);

        $result = $method->invoke($this->controller, $columns);

        // Should detect all nested relationships
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.kind', $result);
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
    }

    /**
     * Test Case 5: Fallback relationships (pipe-separated)
     * Example: 'reportedTempData.type.name|reportedData.select_type'
     */
    public function testFallbackRelationshipsAreBothDetected(): void
    {
        $method = $this->getMethod('autoDetectRelationships');

        $columns = collect([
            ['key' => 'reportedTempData.type.name|reportedData.select_type', 'label' => 'Type'],
        ]);

        $result = $method->invoke($this->controller, $columns);

        // Both sides of the pipe must be detected
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
        $this->assertContains('reportedData', $result);
    }

    /**
     * Test Case 6: The critical bug fix - optimization must NOT filter out nested relationships
     */
    public function testOptimizationDoesNotFilterOutNestedRelationships(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        // Simulate the scenario that was causing N+1
        $relationships = [
            'reportedTempData',
            'reportedTempData.type', // This was being filtered out before the fix!
        ];

        $result = $method->invoke($this->controller, $relationships);

        // CRITICAL TEST: Both must be in the result
        $this->assertContains(
            'reportedTempData',
            $result,
            'Parent relationship must be included'
        );
        $this->assertContains(
            'reportedTempData.type',
            $result,
            'CRITICAL: Nested relationship must NOT be filtered out! This was the N+1 bug.'
        );
    }

    /**
     * Test Case 7: Duplicate removal
     */
    public function testDuplicateRelationshipsAreRemoved(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        $input = [
            'product',
            'product.platform',
            'product', // duplicate
            'product.platform', // duplicate
        ];

        $result = $method->invoke($this->controller, $input);

        // Should have unique values only
        $this->assertCount(2, $result);
        $this->assertEquals(count($result), count(array_unique($result)));
    }

    /**
     * Test Case 8: Edge case - empty input
     */
    public function testEmptyInputReturnsEmptyArray(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        $result = $method->invoke($this->controller, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test Case 9: Edge case - single level relationships
     */
    public function testSingleLevelRelationshipsArePreserved(): void
    {
        $method = $this->getMethod('extractNestedRelationships');

        $input = ['product', 'user', 'team'];

        $result = $method->invoke($this->controller, $input);

        $this->assertContains('product', $result);
        $this->assertContains('user', $result);
        $this->assertContains('team', $result);
        $this->assertCount(3, $result);
    }

    /**
     * Test Case 10: Relationship extraction from individual key
     */
    public function testRelationshipExtractionFromKey(): void
    {
        $method = $this->getMethod('extractRelationshipsFromKey');
        $relationships = [];

        // Test nested key
        $method->invokeArgs($this->controller, ['product.platform.name', &$relationships]);

        $this->assertContains('product', $relationships);
        $this->assertContains('product.platform', $relationships);
    }

    /**
     * Test Case 11: Real-world Vulnerability Controller scenario
     */
    public function testVulnerabilityControllerScenario(): void
    {
        $method = $this->getMethod('autoDetectRelationships');

        // Actual columns from VulnerabilityController
        $columns = collect([
            ['key' => 'id'],
            ['key' => 'product.platform.name'],
            ['key' => 'type.name'],
            ['key' => 'product.kind.kind_name'],
        ]);

        $result = $method->invoke($this->controller, $columns);

        // Must detect all relationships to prevent N+1
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.kind', $result);
        $this->assertContains('type', $result);
    }

    /**
     * Test Case 12: Real-world Report Controller scenario
     */
    public function testReportControllerScenario(): void
    {
        $method = $this->getMethod('autoDetectRelationships');

        // Actual columns from ReportController
        $columns = collect([
            ['key' => 'id'],
            ['key' => 'reportedData.comp_name'],
            ['key' => 'reportedTempData.type.name|reportedData.select_type'],
            ['key' => 'reportedTempData.affected_in|reportedData.vuln_version'],
        ]);

        $result = $method->invoke($this->controller, $columns);

        // Must detect all relationships
        $this->assertContains('reportedData', $result);
        $this->assertContains('reportedTempData', $result);
        $this->assertContains('reportedTempData.type', $result);
    }

    /**
     * Helper to access private methods
     */
    protected function getMethod(string $methodName): ReflectionMethod
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
