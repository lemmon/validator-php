# Lemmon Validator - Future Ideas

This document captures innovative ideas and suggestions for potential future enhancements to the Lemmon Validator library. These concepts represent opportunities for expanding the library's capabilities beyond the current roadmap.

## ✅ Recently Implemented

### Type-Aware Transformation System (v0.5.0)
**Status**: ✅ **IMPLEMENTED**
**Concept**: Revolutionary transformation system with intelligent type context switching.
```php
// Enhanced coercion - empty strings to null for form safety
$age = Validator::isInt()->coerce()->validate(''); // Returns: null (not dangerous 0)
$price = Validator::isFloat()->coerce()->validate(''); // Returns: null (not dangerous 0.0)

// Array filtering with auto-reindexing
$tags = Validator::isArray()->filterEmpty()->validate(['php', '', 'javascript', null]);
// Returns: ['php', 'javascript'] (properly reindexed)

// Universal transformations - available on ALL validators
$name = Validator::isString()
    ->pipe('trim', 'strtoupper')  // Multiple string operations, maintains type
    ->validate('  john  '); // Returns: "JOHN"

// Multiple transformations with pipe
$slug = Validator::isString()
    ->pipe('trim', 'strtolower', fn($v) => str_replace(' ', '-', $v))
    ->validate('Hello World'); // Returns: "hello-world"

// Type-aware transformation chains
$result = Validator::isArray()
    ->pipe('array_unique', 'array_reverse')        // Array operations (auto-reindexed)
    ->transform(fn($v) => implode(',', $v))        // Array → String (type switch)
    ->pipe('trim', 'strtoupper')                   // String operations
    ->transform('strlen')                          // String → Int (type switch)
    ->validate(['a', 'b', 'a']); // Returns: 3

// Integration with external libraries
$formatted = Validator::isString()
    ->pipe('trim', fn($v) => Str::title($v), fn($v) => Str::limit($v, 50))
    ->validate('hello world'); // Laravel Str integration
```
**Benefits**:
- ✅ Form-safe empty string handling (prevents dangerous zero defaults)
- ✅ Maintains validator type contracts (indexed arrays stay indexed)
- ✅ Preserves valid falsy values (0, false, [])
- ✅ Type-aware transformation methods with intelligent context switching
- ✅ `pipe()` maintains type, `transform()` can change type
- ✅ Smart array coercion (indexed arrays auto-reindex, associative keys preserved)
- ✅ Clean variadic syntax with `pipe(...$transformers)`
- ✅ Perfect integration with external libraries (Laravel, Symfony)
- ✅ Comprehensive test coverage (127 tests, 363 assertions)
- ✅ Complete documentation overhaul with comprehensive coverage across all guides
- ✅ Fixed all dead links in documentation for seamless navigation
- ✅ Added type-aware transformation system documentation with detailed examples
- ✅ Documented `filterEmpty()` method with practical use cases
- ✅ Updated API reference to match actual implementation
- ✅ Fixed critical schema validation bug - only include provided fields or fields with defaults in results
- ✅ Enhanced form-safe coercion - empty strings coerce to empty objects/arrays for better form handling
- ✅ Fixed floating-point precision bug in multipleOf validation for accurate decimal calculations

### Intuitive Custom Validation (v0.6.0)
**Status**: ✅ **IMPLEMENTED**
**Concept**: Natural language custom validation with optional error messages.
```php
// Natural, readable validation
$positiveNumber = Validator::isInt()->satisfies(fn($v) => $v > 0);

// Multiple conditions with fluent chaining
$strongPassword = Validator::isString()
    ->minLength(8)
    ->satisfies(fn($v) => preg_match('/[A-Z]/', $v), 'Must contain uppercase')
    ->satisfies(fn($v) => preg_match('/[0-9]/', $v), 'Must contain number')
    ->satisfies(fn($v) => preg_match('/[!@#$%^&*]/', $v), 'Must contain special character');

// Context-aware validation
$passwordConfirm = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        return isset($input['password']) && $value === $input['password'];
    },
    'Password confirmation must match'
);

// Business logic validation
$workingAge = Validator::isInt()
    ->satisfies(fn($age) => $age >= 16, 'Must be at least 16 to work')
    ->satisfies(fn($age) => $age <= 65, 'Must be under retirement age');
```
**Benefits**:
- ✅ Natural language: "value must satisfy this condition"
- ✅ Optional error messages with sensible defaults
- ✅ Backward compatibility with deprecated `addValidation()`
- ✅ Comprehensive documentation updates (65+ references)
- ✅ Enhanced developer experience and API discoverability
- ✅ Complete API documentation with accurate method signatures
- ✅ Fixed documentation inconsistencies and removed unimplemented features

### Static Logical Combinators (v0.4.0)
**Status**: ✅ **IMPLEMENTED**
**Concept**: Static factory methods for advanced validation logic.
```php
// Mixed-type validation
$flexibleId = Validator::anyOf([
    Validator::isInt()->positive(),
    Validator::isString()->uuid(),
    Validator::isString()->pattern('/^[A-Z]{3}-\d{4}$/')
]);

// Multiple constraints
$strictString = Validator::allOf([
    Validator::isString()->minLength(5),
    Validator::isString()->maxLength(20),
    Validator::isString()->pattern('/^[A-Za-z]+$/')
]);

// Exclusion logic
$notBanned = Validator::not(
    Validator::isString()->oneOf(['banned', 'suspended']),
    'User cannot be banned or suspended'
);
```
**Benefits**:
- ✅ Clean syntax for mixed-type validation
- ✅ Type-agnostic logical operations
- ✅ Enhanced API consistency
- ✅ Simplified array validation with mixed item types

## 💡 Core Enhancement Ideas

### 1. Advanced Type Conversions
**Concept**: Built-in transformations for common type conversions.
```php
Validator::isString()
    ->datetime()
    ->toDateTime() // Built-in DateTime conversion
    ->validate('2023-12-25T15:30:00Z'); // Returns DateTime object

Validator::isString()
    ->json()
    ->toArray() // Built-in JSON parsing
    ->validate('{"name": "John"}'); // Returns array
```
**Benefits**:
- Common conversions as first-class methods
- Type-safe transformations with proper error handling
- Reduced boilerplate for frequent operations

### 2. Schema Manipulation Utilities
**Concept**: Advanced schema composition and manipulation methods.
```php
$userSchema = Validator::isAssociative([...]);

// Create variations
$partialUser = $userSchema->partial(); // All fields optional (PATCH requests)
$publicUser = $userSchema->omit(['password', 'internal_id']);
$extendedUser = $userSchema->merge($additionalFields);
```
**Benefits**:
- Schema reusability and DRY principles
- API versioning support
- Reduced boilerplate for schema variations

### 3. Enhanced Error Context
**Concept**: Full path error reporting for nested structures.
```php
// Instead of: ['street' => ['Value is required']]
// Provide: ['user.address.street' => ['Value is required']]
```
**Benefits**:
- Improved debugging for complex nested data
- Better user experience in form validation
- Easier error handling in frontend applications

### 4. Programmatic Error Codes
**Concept**: Structured error codes for programmatic error handling.
```php
try {
    $validator->validate($data);
} catch (ValidationException $e) {
    foreach ($e->getStructuredErrors() as $error) {
        match($error->getCode()) {
            'STRING_TOO_SHORT' => handleLengthError($error),
            'INVALID_EMAIL' => handleEmailError($error),
            default => handleGenericError($error)
        };
    }
}
```
**Benefits**:
- Better integration with error tracking systems
- Internationalization support
- Programmatic error handling capabilities

## 🔧 Developer Experience Ideas

### 5. Schema Serialization
**Concept**: Export/import validation schemas for cross-platform use.
```php
$schema = Validator::isAssociative([...]);
$json = $schema->toJson(); // Export for frontend
$recreated = Validator::fromJson($json); // Recreate from config
```
**Benefits**:
- Frontend/backend schema synchronization
- Configuration-driven validation
- Schema documentation generation

### 6. Performance Profiling
**Concept**: Optional performance monitoring for complex validation chains.
```php
$profiler = new ValidationProfiler();
$result = $validator->withProfiler($profiler)->validate($data);
$report = $profiler->getReport(); // Identify bottlenecks
```
**Benefits**:
- Performance optimization guidance
- Bottleneck identification in complex schemas
- Production performance monitoring

### 7. Validation Middleware
**Concept**: Framework-agnostic validation middleware pattern.
```php
$middleware = ValidationMiddleware::create($schema);
$app->use($middleware); // Auto-validate requests
```
**Benefits**:
- Framework integration simplification
- Consistent validation across application layers
- Reduced boilerplate in controllers

## 🌐 Integration Ideas

### 8. OpenAPI Schema Generation
**Concept**: Generate OpenAPI specifications from validation schemas.
```php
$schema = Validator::isAssociative([...]);
$openApi = $schema->toOpenApiSchema(); // Generate API documentation
```
**Benefits**:
- Automatic API documentation
- Schema-driven development
- Frontend SDK generation

### 9. Database Schema Validation
**Concept**: Validate data against database schema constraints.
```php
$validator = Validator::fromDatabaseTable('users');
$user = $validator->validate($userData); // Respects DB constraints
```
**Benefits**:
- Database-driven validation rules
- Consistency between application and database layers
- Reduced schema maintenance overhead

### 10. Async Validation Support
**Concept**: Support for asynchronous validation operations using PHP async libraries.
```php
// Using ReactPHP or Amp for async operations
$validator = Validator::isString()
    ->addAsyncValidation(
        function($value) use ($httpClient) {
            return $httpClient->validateEmailDeliverability($value); // Returns Promise
        },
        'Email address is not deliverable'
    );

// ReactPHP style
$promise = $validator->validateAsync($email);
$promise->then(function($result) {
    // Handle valid result
}, function($error) {
    // Handle validation error
});

// Or with Amp
$result = yield $validator->validateAsync($email);
```
**Benefits**:
- External service integration without blocking
- Compatible with ReactPHP/Amp async ecosystems
- Enhanced validation capabilities for I/O operations

## 🚀 Advanced Features

### 11. Conditional Schema Selection
**Concept**: Dynamic schema selection based on input data.
```php
$validator = Validator::conditional(
    fn($data) => $data['type'] ?? 'user',
    [
        'user' => $userSchema,
        'admin' => $adminSchema,
        'guest' => $guestSchema
    ]
);
```
**Benefits**:
- Polymorphic data validation
- Reduced schema complexity
- Dynamic validation logic

### 12. Validation Caching
**Concept**: Cache validation results for expensive operations.
```php
$validator = Validator::isString()
    ->addValidation($expensiveValidation)
    ->withCache($cacheAdapter, ttl: 3600);
```
**Benefits**:
- Performance optimization for expensive validations
- Reduced external service calls
- Scalability improvements

## 📊 Analytics Ideas

### 13. Validation Analytics
**Concept**: Collect validation metrics for insights.
```php
$analytics = new ValidationAnalytics();
$validator->withAnalytics($analytics);
// Track: most common errors, validation performance, usage patterns
```
**Benefits**:
- Data-driven validation improvements
- User behavior insights
- Quality metrics tracking

### 14. A/B Testing for Validation Rules
**Concept**: Test different validation strategies.
```php
$validator = Validator::isString()
    ->abTest('email_validation', [
        'strict' => fn($v) => $strictEmailValidator->validate($v),
        'lenient' => fn($v) => $lenientEmailValidator->validate($v)
    ]);
```
**Benefits**:
- Validation rule optimization
- User experience testing
- Data-driven decision making

## 🎯 Implementation Priority

These ideas are organized by potential impact and implementation complexity:

**High Impact, Low Complexity**:
- Data Transformation Pipeline
- Schema Manipulation Utilities
- Enhanced Error Context

**High Impact, Medium Complexity**:
- Programmatic Error Codes
- Schema Serialization
- Performance Profiling

**High Impact, High Complexity**:
- Async Validation Support
- OpenAPI Schema Generation
- Database Schema Validation

**Research & Exploration**:
- Validation Analytics
- A/B Testing for Validation Rules
- Conditional Schema Selection

## 📝 Notes

- Ideas are not committed features and may evolve based on community feedback
- Implementation priority depends on user demand and project resources
- Some concepts may be better suited for separate packages or extensions
- All ideas should maintain backward compatibility and the library's core philosophy of simplicity and type safety
