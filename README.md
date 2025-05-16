# ArrayDataProcessor

A powerful and flexible PHP utility for processing, filtering, and transforming arrays of data with support for type casting, field aliasing, and multiple output formats.

## Features

- 🔄 Custom filter callbacks
- 🔀 AND/OR filter logic
- 📝 Field type casting (built-in and custom)
- 🏷️ Field aliasing
- 📤 Multiple output formats (array, JSON, CSV)
- 📊 Sorting and pagination
- 📝 Rich logging support
- ⚡ Performance optimized
- 🛡️ Error handling

## Installation

```bash
composer require peeks/array-data-processor
```

## Basic Usage

```php
use Peeks\Lib\ArrayDataProcessor;

// Initialize with data
$processor = new ArrayDataProcessor($data);

// Configure and process
$result = $processor
    ->setFieldType('id', 'int')
    ->setFieldType('amount', function($value) {
        return number_format($value / 100, 2);
    })
    ->setFilters([
        'status' => ['type' => 'equals', 'value' => 'active']
    ])
    ->process();
```

## Advanced Example

Here's a comprehensive example showing all features:

```php
// Sample data array with rich information
$data = [
    [
        'stream_id' => '1001',
        'user_id' => '5001',
        'title' => 'Gaming Stream',
        'viewer_count' => '1500',
        'is_featured' => 'Y',
        'rating' => '4.50',
        'amount' => '2500', // Amount in cents
        'created_at' => '2025-05-16 08:00:00',
        'tags' => 'gaming,live,esports',
        'metadata' => json_encode([
            'device' => 'mobile',
            'quality' => 'HD'
        ])
    ]
];

// Initialize processor
$processor = new ArrayDataProcessor($data);

// Configure type casting
$processor
    // Basic type casting
    ->setFieldType('stream_id', 'int')
    ->setFieldType('viewer_count', 'int')
    
    // Custom type casting
    ->setFieldType('amount', function($value) {
        return number_format($value / 100, 2, '.', '');
    })
    ->setFieldType('created_at', function($value) {
        return new DateTime($value);
    })
    ->setFieldType('tags', function($value) {
        return array_map('trim', explode(',', $value));
    })
    ->setFieldType('metadata', function($value) {
        return json_decode($value, true);
    });

// Set up filters
$processor->setFilters([
    'viewer_count' => [
        'type' => 'greaterThan',
        'value' => 1000
    ],
    'is_featured' => [
        'type' => 'equals',
        'value' => 'Y'
    ]
]);

// Set field aliases
$processor
    ->setFieldAlias('viewer_count', 'viewers')
    ->setFieldAlias('created_at', 'streamDate');

// Select output fields
$processor->setFields([
    'stream_id',
    'title',
    'viewers',
    'rating',
    'streamDate',
    'tags',
    'metadata'
]);

// Process data
$result = $processor->process();
```

## Available Methods

### Type Casting
- `setFieldType(string $field, string|callable $type)`: Set type casting for a field
  - Built-in types: 'int', 'float', 'bool', 'string', 'array'
  - Custom casting via callback function

### Filtering
- `setFilters(array $filters)`: Set filter conditions
- `setFilterLogic(string $logic)`: Set filter logic ('AND'/'OR')
- `addCustomFilter(string $field, callable $callback)`: Add custom filter

### Field Management
- `setFields(array $fields)`: Select fields for output
- `setFieldAlias(string $original, string $alias)`: Set field aliases

### Output Formatting
- `setOutputFormat(string $format)`: Set output format ('array'/'json'/'csv')

### Pagination
- `setLimit(?int $limit)`: Set maximum items to return
- `setOffset(?int $offset)`: Set number of items to skip

### Sorting
- `setSort(array $sort)`: Set sorting criteria

## Filter Types

- `equals`
- `notEquals`
- `contains`
- `startsWith`
- `endsWith`
- `in`
- `notIn`
- `greaterThan`
- `lessThan`
- `between`
- `before`
- `after`
- `isNull`
- `isNotNull`

## Error Handling

The processor includes comprehensive error handling and logging:

```php
try {
    $result = $processor->process();
} catch (\InvalidArgumentException $e) {
    // Handle validation errors
} catch (\Exception $e) {
    // Handle processing errors
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
