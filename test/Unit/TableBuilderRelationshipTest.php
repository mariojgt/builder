<?php

namespace Mariojgt\Builder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mariojgt\Builder\Controllers\TableBuilderApiController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for TableBuilderApiController N+1 query prevention
 */
class TableBuilderRelationshipTest extends TestCase
{
    protected TableBuilderApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TableBuilderApiController();
    }

    /**
     * Test that extractNestedRelationships method correctly expands nested relationships
     */
    public function testExtractNestedRelationshipsExpandsCorrectly(): void
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
     * Test that extractNestedRelationships handles single-level relationships
     */
    public function testExtractNestedRelationshipsHandlesSingleLevel(): void
    {
        $relationships = ['product', 'type', 'user'];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        // Single-level relationships should be preserved
        $this->assertContains('product', $result);
        $this->assertContains('type', $result);
        $this->assertContains('user', $result);
        $this->assertCount(3, $result);
    }

    /**
     * Test that extractNestedRelationships handles empty array
     */
    public function testExtractNestedRelationshipsHandlesEmptyArray(): void
    {
        $relationships = [];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that extractNestedRelationships handles deep nesting
     */
    public function testExtractNestedRelationshipsHandlesDeepNesting(): void
    {
        $relationships = [
            'product.platform.company.country.name',
        ];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        // Should expand all levels
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
        $this->assertContains('product.platform.company', $result);
        $this->assertContains('product.platform.company.country', $result);
    }

    /**
     * Test that extractNestedRelationships removes duplicates
     */
    public function testExtractNestedRelationshipsRemovesDuplicates(): void
    {
        $relationships = [
            'product.platform',
            'product',
            'product.platform.name',
            'product',
        ];

        $method = $this->getPrivateMethod('extractNestedRelationships');
        $result = $method->invoke($this->controller, $relationships);

        // Should have unique values only
        $this->assertEquals(count($result), count(array_unique($result)));

        // Should contain both levels
        $this->assertContains('product', $result);
        $this->assertContains('product.platform', $result);
    }

    /**
     * Test that extractRelationshipsFromKey correctly identifies relationships
     */
    public function testExtractRelationshipsFromKeyIdentifiesRelationships(): void
    {
        $relationships = [];
        $method = $this->getPrivateMethod('extractRelationshipsFromKey');

        // Test single level
        $method->invokeArgs($this->controller, ['product.name', &$relationships]);
        $this->assertContains('product', $relationships);

        // Test nested level
        $relationships = [];
        $method->invokeArgs($this->controller, ['product.platform.name', &$relationships]);
        $this->assertContains('product', $relationships);
        $this->assertContains('product.platform', $relationships);

        // Test without dot notation
        $relationships = [];
        $method->invokeArgs($this->controller, ['name', &$relationships]);
        $this->assertEmpty($relationships);
    }

    /**
     * Test that autoDetectRelationships correctly identifies relationships from columns
     */
    public function testAutoDetectRelationshipsFromColumns(): void
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
     */
    public function testAutoDetectRelationshipsHandlesFallbackKeys(): void
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
     * Test that autoDetectRelationships handles model_search type
     */
    public function testAutoDetectRelationshipsHandlesModelSearch(): void
    {
        $columns = collect([
            [
                'key' => 'user_id',
                'type' => 'model_search',
                'relation' => 'user',
            ],
            [
                'key' => 'product_id',
                'type' => 'model_search',
                'relation' => 'product.platform',
            ],
        ]);

        $method = $this->getPrivateMethod('autoDetectRelationships');
        $result = $method->invoke($this->controller, $columns);

        // Should detect relationships from 'relation' field
        $this->assertContains('user', $result);
        $this->assertContains('product.platform', $result);
    }

    /**
     * Helper method to access private methods for testing
     */
    protected function getPrivateMethod(string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass(TableBuilderApiController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
