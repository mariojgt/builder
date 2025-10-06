# Builder Package Tests

## Overview

This test suite verifies that the N+1 query prevention mechanisms in `TableBuilderApiController` are working correctly.

## Test Structure

### Unit Tests (`test/Unit/`)
- `TableBuilderRelationshipTest.php` - Tests for relationship detection and expansion methods
  - Tests `extractNestedRelationships()` method
  - Tests `autoDetectRelationships()` method
  - Tests `extractRelationshipsFromKey()` method
  - Tests handling of fallback relationships (pipe-separated)
  - Tests edge cases (empty arrays, single-level relationships, deep nesting)

### Feature Tests (`test/Feature/`)
- `TableBuilderN1PreventionTest.php` - Integration tests with real database queries
  - Tests that nested relationships don't cause N+1 queries
  - Tests with multiple nested relationships
  - Tests with fallback relationships
  - Tests response structure

## Running Tests

### From Host Machine

#### Run all tests:
```bash
docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test
```

#### Run only unit tests:
```bash
docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test/Unit
```

#### Run only feature tests:
```bash
docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test/Feature
```

#### Run specific test class:
```bash
docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test/Unit/TableBuilderRelationshipTest.php
```

#### Run with verbose output:
```bash
docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test --verbose
```

### From Inside Docker Container

```bash
docker exec -it Hub_app bash
cd /var/www/html
vendor/bin/phpunit vendor/mariojgt/builder/test
```

## What The Tests Verify

### 1. Relationship Detection
- ✅ Correctly identifies relationships from column keys like `product.platform.name`
- ✅ Expands nested relationships to include all parent levels
- ✅ Handles pipe-separated fallback keys like `reportedTempData.type.name|reportedData.select_type`
- ✅ Handles `model_search` type columns with `relation` field

### 2. Relationship Optimization
- ✅ Keeps all necessary relationships (doesn't filter out nested ones)
- ✅ Removes duplicate relationships
- ✅ Validates that relationships exist on the model

### 3. N+1 Query Prevention
- ✅ Loading table data with nested relationships stays under 10 queries
- ✅ Query count doesn't increase linearly with number of rows (N+1 prevented)
- ✅ Works with multiple nested relationships simultaneously
- ✅ Works with fallback relationships

### 4. Response Structure
- ✅ Response maintains correct structure with eager loaded data
- ✅ Nested relationship data is accessible in the response

## Test Coverage

The tests cover the following methods in `TableBuilderApiController`:

- `extractNestedRelationships()` - Expands nested relationships
- `autoDetectRelationships()` - Detects relationships from column config
- `extractRelationshipsFromKey()` - Extracts relationships from individual keys
- `optimizeRelationshipLoading()` - Optimizes the relationship list
- `index()` - Main controller method (integration test)

## Expected Test Results

### Unit Tests
All unit tests should pass without database interaction:
```
OK (15 tests, 45 assertions)
```

### Feature Tests
Feature tests require a working database. Some may be skipped if:
- Database tables don't exist
- Test data can't be created
- Models don't have expected relationships

Expected output:
```
OK (4 tests, 12 assertions)
Tests: 4, Assertions: 12, Skipped: 0
```

Or if database is not set up:
```
OK, but incomplete, skipped, or risky tests!
Tests: 4, Assertions: 0, Skipped: 4
```

## Troubleshooting

### "Database tables not available"
Feature tests are skipped because required tables don't exist. This is expected in some environments. Unit tests will still run.

### "Could not execute query"
The test couldn't run the controller method. Check:
1. Models exist and are properly configured
2. Database connection is working
3. Relationships are properly defined on models

### "Too many queries"
If this assertion fails, it means N+1 queries are still happening. Check:
1. `extractNestedRelationships()` is being called
2. `optimizeRelationshipLoading()` is not filtering out nested relationships
3. Debug logging shows relationships are being eager loaded

## Performance Benchmarks

With the N+1 fix in place:

| Scenario | Expected Queries | Max Acceptable |
|----------|-----------------|----------------|
| Simple table (1 nested relationship) | 2-3 | 7 |
| Complex table (multiple nested) | 3-5 | 10 |
| With fallback relationships | 3-6 | 10 |

Before the fix, query counts would be:
- 1 main query + N queries for each relationship per row
- Example: 1 + (48 rows × 1 relationship) = 49 queries

## CI/CD Integration

Add to your CI pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Builder Tests
  run: docker exec Hub_app vendor/bin/phpunit vendor/mariojgt/builder/test --testdox
```

## Adding New Tests

When adding new relationship features, add tests in:

1. **Unit tests** - For testing individual methods with mocked data
2. **Feature tests** - For testing complete flows with real database

Example:
```php
public function testNewFeature(): void
{
    // Arrange
    $data = [...];
    
    // Act
    $result = $this->controller->newMethod($data);
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

## License

Same as the Builder package - MIT License
