# Testing Guide for INHouse Backend

This directory contains all the tests for the INHouse Laravel backend application.

## Test Structure

- **Unit Tests** (`tests/Unit/`): Test individual classes and methods in isolation
- **Feature Tests** (`tests/Feature/`): Test complete features and API endpoints
- **Test Helpers** (`tests/TestHelpers.php`): Common testing utilities and assertions

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suites
```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite=Unit

# Run only feature tests
./vendor/bin/phpunit --testsuite=Feature
```

### Run Specific Test Files
```bash
# Run specific test file
./vendor/bin/phpunit tests/Unit/UserTest.php

# Run specific test method
./vendor/bin/phpunit --filter test_that_true_is_true
```

### Run Tests with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Test Configuration

The testing environment is configured in `phpunit.xml` with:
- SQLite in-memory database for fast, isolated tests
- Testing environment variables
- Proper test suite organization

## Available Test Traits

### RefreshDatabase
Automatically refreshes the database between tests to ensure isolation.

### WithFaker
Provides access to fake data generation for realistic test scenarios.

### TestHelpers
Custom helper methods for common testing operations:
- `createUser()` - Create a test user
- `createOAuthClient()` - Create a test OAuth client
- `createInventory()` - Create a test inventory item
- `assertModelHasAttributes()` - Assert model attributes
- `assertModelExists()` - Assert model exists in database
- `assertModelMissing()` - Assert model doesn't exist in database

## Writing Tests

### Unit Tests
Test individual classes and methods:
```php
/** @test */
public function it_can_create_a_user()
{
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->assertInstanceOf(User::class, $user);
    $this->assertEquals('John Doe', $user->name);
}
```

### Feature Tests
Test complete API endpoints:
```php
/** @test */
public function user_can_register_with_valid_data()
{
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
}
```

## Test Data

### Factories
Use Laravel factories to create test data:
```php
$user = User::factory()->create();
$inventory = Inventory::factory()->create(['name' => 'Test Product']);
```

### Database Assertions
```php
$this->assertDatabaseHas('users', ['email' => 'john@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
```

## Best Practices

1. **Test Isolation**: Each test should be independent and not rely on other tests
2. **Descriptive Names**: Use clear, descriptive test method names
3. **Arrange-Act-Assert**: Structure tests with clear sections
4. **Test One Thing**: Each test should verify one specific behavior
5. **Use Factories**: Generate realistic test data with factories
6. **Clean Up**: Use `RefreshDatabase` trait to ensure clean state between tests

## Common Assertions

### Response Assertions
```php
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertJsonValidationErrors(['email']);
```

### Database Assertions
```php
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);
```

### Model Assertions
```php
$this->assertInstanceOf(User::class, $user);
$this->assertTrue($user->exists);
$this->assertEquals('expected', $user->attribute);
```
