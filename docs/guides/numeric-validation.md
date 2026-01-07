# Numeric Validation Guide

The Lemmon Validator provides two distinct numeric validators: `IntValidator` for integers and `FloatValidator` for floating-point numbers. Both share common numeric constraints through the `NumericConstraintsTrait`.

## Integer Validation

### Basic Integer Validation

```php
use Lemmon\Validator\Validator;

$intValidator = Validator::isInt();
$result = $intValidator->validate(42); // Returns: 42 (int)

// With coercion from string
$coercingValidator = Validator::isInt()->coerce();
$result = $coercingValidator->validate('123'); // Returns: 123 (int)
```

### Integer Constraints

All numeric constraint methods are available for integers:

```php
$constrainedInt = Validator::isInt()
    ->min(0)                    // Minimum value
    ->max(100)                  // Maximum value
    ->positive()                // Must be > 0
    ->multipleOf(5);            // Must be divisible by 5

$result = $constrainedInt->validate(25); // Valid (0 ≤ 25 ≤ 100, 25 > 0, 25 % 5 = 0)
```

### Port Number Validation

Port numbers are integers in the range 1-65535. Use the `port()` method for network/API validation:

```php
$portValidator = Validator::isInt()->port();

// Valid ports
$port = $portValidator->validate(80);      // HTTP
$port = $portValidator->validate(443);     // HTTPS
$port = $portValidator->validate(3000);    // Common dev port
$port = $portValidator->validate(65535);   // Maximum port
$port = $portValidator->validate(1);        // Minimum port

// With coercion (for string inputs from HTTP/config)
$portValidator = Validator::isInt()->coerce()->port();
$port = $portValidator->validate('80');    // Returns: 80 (int)
$port = $portValidator->validate('443');   // Returns: 443 (int)

// Invalid: out of range
// $portValidator->validate(0);        // ❌ ValidationException (too low)
// $portValidator->validate(65536);    // ❌ ValidationException (too high)
// $portValidator->validate(-1);        // ❌ ValidationException (negative)

// Custom message
$customPort = Validator::isInt()->port('Must be a valid port number');
```

## Float Validation

### Basic Float Validation

```php
$floatValidator = Validator::isFloat();
$result = $floatValidator->validate(3.14159); // Returns: 3.14159 (float)

// With coercion
$coercingFloat = Validator::isFloat()->coerce();
$result = $coercingFloat->validate('123.45'); // Returns: 123.45 (float)
$result = $coercingFloat->validate(42); // Returns: 42.0 (float)
```

### Float Constraints

Same constraint methods as integers, but with float precision:

```php
$constrainedFloat = Validator::isFloat()
    ->min(0.0)                  // Minimum value
    ->max(100.0)                // Maximum value
    ->positive()                // Must be > 0.0
    ->multipleOf(0.01);         // Precision to cents

$result = $constrainedFloat->validate(19.99); // Valid price with cent precision
```

## Shared Numeric Constraints

Both `IntValidator` and `FloatValidator` use the `NumericConstraintsTrait`, providing these methods:

### Range Constraints

```php
// Minimum value
$minValidator = Validator::isInt()->min(18);
$age = $minValidator->validate(25); // Valid

// Maximum value
$maxValidator = Validator::isFloat()->max(100.0);
$percentage = $maxValidator->validate(85.5); // Valid

// Combined range
$rangeValidator = Validator::isInt()->min(1)->max(10);
$rating = $rangeValidator->validate(8); // Valid
```

### Sign Constraints

```php
// Positive numbers (> 0)
$positiveValidator = Validator::isFloat()->positive();
$price = $positiveValidator->validate(29.99); // Valid

// Negative numbers (< 0)
$negativeValidator = Validator::isInt()->negative();
$debt = $negativeValidator->validate(-1000); // Valid

// Non-negative (>= 0)
$nonNegative = Validator::isInt()->nonNegative();
$zeroOrMore = $nonNegative->validate(0); // Valid

// Non-positive (<= 0)
$nonPositive = Validator::isFloat()->nonPositive();
$zeroOrLess = $nonPositive->validate(0.0); // Valid

// Note: Zero fails both positive() and negative()
```

### Multiple Validation

```php
// Integer multiples
$evenValidator = Validator::isInt()->multipleOf(2);
$evenNumber = $evenValidator->validate(42); // Valid

// Float multiples (useful for precision)
$centValidator = Validator::isFloat()->multipleOf(0.01);
$price = $centValidator->validate(19.99); // Valid (cent precision)

// Custom multiples
$quarterValidator = Validator::isFloat()->multipleOf(0.25);
$quarters = $quarterValidator->validate(2.75); // Valid (11 quarters)
```

### Comparison Helpers

```php
Validator::isInt()->gt(10);     // > 10
Validator::isInt()->gte(10);    // >= 10
Validator::isFloat()->lt(5.5);  // < 5.5
Validator::isFloat()->lte(5.5); // <= 5.5
```

### Clamping Values (Transformation)

Use `clampToRange(min, max)` to keep values in range without extra conditionals. This is a transformation step (pipeline), not a validation rule:

```php
$score = Validator::isInt()->clampToRange(0, 100);
expect($score->validate(150))->toBe(100);
expect($score->validate(-10))->toBe(0);

$normalized = Validator::isFloat()->clampToRange(-1.0, 1.0);
expect($normalized->validate(2.0))->toBe(1.0);
```

## Type Coercion

### Integer Coercion

```php
$coercingInt = Validator::isInt()->coerce();

// String to int
$result = $coercingInt->validate('123'); // Returns: 123 (int)
$result = $coercingInt->validate('-456'); // Returns: -456 (int)

// Float to int (truncates)
$result = $coercingInt->validate(123.89); // Returns: 123 (int)

// Invalid coercion
try {
    $coercingInt->validate('not-a-number'); // ValidationException
} catch (ValidationException $e) {
    // Handle error
}
```

### Float Coercion

```php
$coercingFloat = Validator::isFloat()->coerce();

// String to float
$result = $coercingFloat->validate('123.45'); // Returns: 123.45 (float)
$result = $coercingFloat->validate('123'); // Returns: 123.0 (float)

// Int to float
$result = $coercingFloat->validate(42); // Returns: 42.0 (float)
```

### Form-Safe Empty String Handling

**BREAKING CHANGE (v0.6.0)**: Empty strings now convert to `null` instead of `0`/`0.0` for real-world form safety.

#### Why This Change Was Critical

Traditional PHP type casting creates dangerous scenarios in form handling:

```php
// ❌ DANGEROUS: Old behavior (PHP default)
$balance = (int) $_POST['balance'];    // Empty field → 0 (dangerous!)
$price = (float) $_POST['price'];      // Empty field → 0.0 (dangerous!)
$quantity = (int) $_POST['quantity'];  // Empty field → 0 (dangerous!)
```

**Real-world problems:**
- Bank balance field left empty → account balance becomes $0
- Product quantity field empty → order quantity becomes 0 items
- Price field empty → product becomes free ($0.00)

#### Safe Coercion Behavior

The Lemmon Validator now treats empty strings as "no value provided":

```php
// SAFE: New behavior
$intValidator = Validator::isInt()->coerce();
$balance = $intValidator->validate(''); // Returns: null (safe!)

$floatValidator = Validator::isFloat()->coerce();
$price = $floatValidator->validate(''); // Returns: null (safe!)
```

#### Practical Form Validation

```php
// Safe form validation schema
$orderValidator = Validator::isAssociative([
    'customer_id' => Validator::isInt()->required(),
    'quantity' => Validator::isInt()->coerce()->required()->min(1), // Empty → null → validation fails (safe!)
    'unit_price' => Validator::isFloat()->coerce()->required()->positive(), // Empty → null → validation fails (safe!)
    'discount' => Validator::isFloat()->coerce()->default(0.0), // Empty → null → 0.0 default (explicit)
]);

$formData = [
    'customer_id' => '123',
    'quantity' => '',    // Empty field - safely handled
    'unit_price' => '',  // Empty field - safely handled
    'discount' => '',    // Empty field - gets default
];

[$valid, $result, $errors] = $orderValidator->tryValidate($formData);
// quantity and unit_price will fail validation (safe!)
// discount will use default value (explicit choice)
```

#### Migration Guide

If you need zero defaults for empty fields, be explicit:

```php
// If you genuinely need zero for empty fields (rare)
$explicitZero = Validator::isInt()
    ->coerce()
    ->default(0)  // Explicit zero default
    ->validate(''); // Returns: 0

// Better: Handle empty values explicitly in your business logic
$quantity = Validator::isInt()->coerce()->validate($input['quantity']);
$finalQuantity = $quantity ?? 1; // Default to 1 if empty, not 0
```

#### Explicit Empty String Nullification

For even more control over empty string handling, use the `nullifyEmpty()` method:

```php
// Explicit nullification (same as coerce() behavior for empty strings)
$explicitValidator = Validator::isInt()
    ->nullifyEmpty() // Empty strings → null
    ->required()     // Null is not allowed
    ->min(1);        // Validation will fail for null (safe!)

$result = $explicitValidator->validate(''); // ❌ "Value is required"

// Combined with defaults for optional numeric fields
$optionalQuantity = Validator::isInt()
    ->nullifyEmpty() // Empty strings → null
    ->default(1);    // Use 1 for null values

$result = $optionalQuantity->validate(''); // Returns: 1
$result = $optionalQuantity->validate('5'); // Returns: 5

// Form-safe optional pricing
$discountValidator = Validator::isFloat()
    ->nullifyEmpty() // Empty strings → null
    ->min(0.0)       // Must be non-negative if provided
    ->default(0.0);  // No discount if empty

// Form data handling
$formData = ['discount' => '']; // Empty discount field
$discount = $discountValidator->validate($formData['discount']); // Returns: 0.0
```

**When to use `nullifyEmpty()`:**
- **Optional numeric fields** where empty should have a default
- **Explicit control** over empty string handling
- **Database schemas** where NULL is preferred over empty strings
- **API endpoints** where empty strings should be normalized to null

## Real-World Examples

### Age Validation

```php
$ageValidator = Validator::isInt()
    ->coerce()                  // Accept string input
    ->min(0, 'Age cannot be negative')
    ->max(150, 'Age cannot exceed 150 years');

$age = $ageValidator->validate('25'); // Returns: 25 (int)
```

### Price Validation

```php
$priceValidator = Validator::isFloat()
    ->coerce()                  // Accept string input from forms
    ->positive('Price must be positive')
    ->multipleOf(0.01, 'Price must be in cents (e.g., 19.99)');

$price = $priceValidator->validate('19.99'); // Returns: 19.99 (float)
```

### Rating System

```php
$ratingValidator = Validator::isInt()
    ->min(1, 'Rating must be at least 1 star')
    ->max(5, 'Rating cannot exceed 5 stars');

$rating = $ratingValidator->validate(4); // Returns: 4 (int)
```

### Percentage Validation

```php
$percentageValidator = Validator::isFloat()
    ->min(0.0, 'Percentage cannot be negative')
    ->max(100.0, 'Percentage cannot exceed 100%');

$percentage = $percentageValidator->validate(85.5); // Returns: 85.5 (float)
```

### Financial Calculations

```php
$moneyValidator = Validator::isFloat()
    ->coerce()
    ->min(0.0, 'Amount cannot be negative')
    ->multipleOf(0.01, 'Amount must be in cents');

$discountValidator = Validator::isFloat()
    ->min(0.0, 'Discount cannot be negative')
    ->max(1.0, 'Discount cannot exceed 100%');

$financialSchema = Validator::isAssociative([
    'amount' => $moneyValidator->required(),
    'discount' => $discountValidator->default(0.0),
    'tax_rate' => Validator::isFloat()
        ->min(0.0)
        ->max(1.0)
        ->default(0.0825), // 8.25% default tax
    'quantity' => Validator::isInt()
        ->coerce()
        ->positive('Quantity must be positive')
        ->default(1)
]);
```

## Advanced Numeric Validation

### Custom Numeric Rules

```php
$evenPositiveValidator = Validator::isInt()
    ->positive()
    ->multipleOf(2)
    ->satisfies(
        fn($value) => $value <= 1000,
        'Value must not exceed 1000'
    );
```

### Range with Custom Logic

```php
$temperatureValidator = Validator::isFloat()
    ->satisfies(
        function ($temp, $key, $input) {
            $unit = $input['unit'] ?? 'celsius';

            return match($unit) {
                'celsius' => $temp >= -273.15 && $temp <= 1000,
                'fahrenheit' => $temp >= -459.67 && $temp <= 1832,
                'kelvin' => $temp >= 0 && $temp <= 1273.15,
                default => false
            };
        },
        'Temperature is outside valid range for the specified unit'
    );

$temperatureSchema = Validator::isAssociative([
    'value' => $temperatureValidator->required(),
    'unit' => Validator::isString()
        ->oneOf(['celsius', 'fahrenheit', 'kelvin'])
        ->default('celsius')
]);
```

### Precision Control

```php
$precisionValidator = Validator::isFloat()
    ->satisfies(
        function ($value) {
            // Ensure no more than 2 decimal places
            return round($value, 2) === $value;
        },
        'Value must have at most 2 decimal places'
    );

$price = $precisionValidator->validate(19.99); // Valid
// $precisionValidator->validate(19.999); // ❌ ValidationException
```

## Error Handling

Numeric validators fail fast per rule:

```php
$strictValidator = Validator::isInt()
    ->min(10)
    ->max(20)
    ->multipleOf(3);

[$valid, $data, $errors] = $strictValidator->tryValidate(5);

// $errors might contain:
// [
//     'Value must be at least 10'
// ]
```

## Performance Tips

1. **Use Appropriate Types**: Choose `isInt()` for integers, `isFloat()` for decimals
2. **Order Constraints**: Put cheaper validations first (min/max before multipleOf)
3. **Avoid Unnecessary Coercion**: Only use `coerce()` when needed

```php
// Efficient ordering
$efficientValidator = Validator::isInt()
    ->min(0)                    // Fast range check
    ->max(1000)                 // Fast range check
    ->multipleOf(7);            // More expensive modulo operation
```

## Common Patterns

### ID Validation

```php
$idValidator = Validator::isInt()
    ->coerce()
    ->positive('ID must be positive');
```

### Currency Validation

```php
$currencyValidator = Validator::isFloat()
    ->coerce()
    ->positive('Amount must be positive')
    ->multipleOf(0.01, 'Amount must be in cents');
```

### Score Validation

```php
$scoreValidator = Validator::isFloat()
    ->min(0.0)
    ->max(100.0)
    ->satisfies(
        fn($score) => round($score, 1) === $score,
        'Score must have at most 1 decimal place'
    );
```

## Next Steps

- [Array Validation Guide](array-validation.md) -- Learn about array and list validation
- [Object & Schema Validation](object-validation.md) -- Handle complex nested structures
- [Custom Validation Guide](custom-validation.md) -- Create custom numeric rules
- [API Reference - Validator Factory](../api-reference/validator-factory.md) -- Complete method reference
