# ArrayDataProcessor

A robust, production-grade PHP class for advanced array data manipulation, filtering, sorting, grouping, and transformation. Designed for flexibility, performance, and clarity, with full support for nested fields, type casting (including closures), aliasing, filtering, sorting, grouping, pagination, and array utilities.

---

## Features
- **Field casting** (including closures and nested/dot notation)
- **Field aliasing** (including nested fields)
- **Filtering** (AND/OR logic, edge cases, custom callbacks)
- **Multi-level sorting**
- **Pagination** (limit/offset)
- **Grouping** by any key (supports dot notation)
- **Array utility methods**: `count`, `first`, `last`, `random`, `reverse`, `shuffle`, `pluck`, `filter`, `map`, `sum`, `avg`, `min`, `max`
- **CSV/JSON export**
- **Operation logging**

---

## Installation
Copy `ArrayDataProcessor.php` (or just the `ArrayDataProcessor` class) into your project. No external dependencies required.

---

## Comprehensive Example Usage

```php
require_once 'ArrayDataProcessor.php';

// --- Example 1: setFields ---
$data = array(
    array('id' => 1, 'name' => 'Alice', 'age' => 30),
    array('id' => 2, 'name' => 'Bob', 'age' => 25),
);
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name'));
echo "\n[setFields] Only id and name:\n";
print_r($processor->toArray());

// --- Example 2: setFieldType ---
$processor = new ArrayDataProcessor($data);
$processor->setFieldType('age', function ($v) {
    return $v . ' years';
});
$processor->setFields(array('id', 'age'));
echo "\n[setFieldType] Age with suffix:\n";
print_r($processor->toArray());

// --- Example 3: setFieldAlias ---
$processor = new ArrayDataProcessor($data);
$processor->setFieldAlias('name', 'username');
$processor->setFields(array('id', 'username'));
echo "\n[setFieldAlias] Alias name as username:\n";
print_r($processor->toArray());

// --- Example 4: setFilters ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name', 'age'));
$processor->setFilters(array('age' => array('type' => 'equals', 'value' => 25)));
echo "\n[setFilters] Only age = 25:\n";
print_r($processor->toArray());

// --- Example 5: addSortBy ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name', 'age'));
$processor->addSortBy('age', 'desc');
echo "\n[addSortBy] Sorted by age desc:\n";
print_r($processor->toArray());

// --- Example 6: setLimit and setOffset ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name', 'age'));
$processor->setLimit(1)->setOffset(1);
echo "\n[setLimit/setOffset] Limit 1, Offset 1:\n";
print_r($processor->toArray());

// --- Example 7: groupBy ---
$groupData = array(
    array('id' => 1, 'type' => 'A'),
    array('id' => 2, 'type' => 'B'),
    array('id' => 3, 'type' => 'A'),
);
$processor = new ArrayDataProcessor($groupData);
$processor->setFields(array('id', 'type'));
$grouped = $processor->groupBy('type');
echo "\n[groupBy] Grouped by type:\n";
print_r($grouped);

// --- Example 8: expandByNestedList ---
$expandData = array(
    array('id' => 1, 'items' => array(array('sku' => 'A'), array('sku' => 'B'))),
    array('id' => 2, 'items' => array()),
);
$processor = new ArrayDataProcessor($expandData);
$processor->expandByNestedList('items', array('item_sku' => 'sku'));
$processor->setFields(array('id', 'item_sku'));
echo "\n[expandByNestedList] Expanded items:\n";
print_r($processor->toArray());

// --- Example 9: pluck ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('name'));
echo "\n[pluck] Names only:\n";
print_r($processor->pluck('name'));

// --- Example 10: map ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'age'));
$mapped = $processor->map(function ($row) {
    $row['age'] += 10;
    return $row;
});
echo "\n[map] Age +10:\n";
print_r($mapped);

// --- Example 11: filter ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'age'));
$filtered = $processor->filter(function ($row) {
    return $row['age'] > 25;
});
echo "\n[filter] Age > 25:\n";
print_r($filtered);

// --- Example 12: sum, avg, min, max ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('age'));
echo "\n[sum] Age sum: " . $processor->sum('age') . "\n";
echo "[avg] Age avg: " . $processor->avg('age') . "\n";
echo "[min] Age min: " . $processor->min('age') . "\n";
echo "[max] Age max: " . $processor->max('age') . "\n";

// --- Example 13: toArray, toJson, toCsv, getCsvHeaders ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name'));
echo "\n[toArray]:\n";
print_r($processor->toArray());
echo "[toJson]:\n";
echo $processor->toJson(JSON_PRETTY_PRINT) . "\n";
echo "[toCsv]:\n";
echo $processor->toCsv() . "\n";
echo "[getCsvHeaders]:\n";
print_r($processor->getCsvHeaders());

// --- Example 14: reset ---
$processor = new ArrayDataProcessor($data);
$processor->setFields(array('id', 'name'));
$processor->reset();
$processor->setFields(array('id', 'age'));
echo "\n[reset] After reset, fields id and age:\n";
print_r($processor->toArray());

// --- Example 15: getLastError ---
$processor = new ArrayDataProcessor($data);
$processor->setFieldType('', 'int'); // Invalid
if ($processor->getLastError()) {
    echo "\n[getLastError]: " . $processor->getLastError() . "\n";
}

// --- Example 16: selectKeys ---
$selectData = array(
    array('id' => 1, 'name' => 'Alpha', 'score' => 10, 'extra' => 'foo'),
    array('id' => 2, 'name' => 'Beta', 'score' => 20, 'extra' => 'bar'),
);
$processor = new ArrayDataProcessor($selectData);
$processor->setFields(array('id', 'name'));
$processor->selectKeys(array('score', 'extra'));
echo "\n[selectKeys] Add score and extra:\n";
print_r($processor->toArray());

// --- Example 17: setEnumMap ---
$statusData = array(
    array('id' => 1, 'status' => 'active'),
    array('id' => 2, 'status' => 'pending'),
);
$processor = new ArrayDataProcessor($statusData);
$processor->setEnumMap(array(
    'status' => array(
        'active' => 'Active',
        'pending' => 'Pending',
    )
));
$processor->setFieldType('status', 'enum');
$processor->setFields(array('id', 'status'));
echo "\n[setEnumMap] Enum mapping for status:\n";
print_r($processor->toArray());


// --- Example 1: Expanding by nested list (accents) ---
$accentData = [
    [
        'id' => 1,
        'name' => 'Voice 1',
        'accents' => [
            ['id' => 101, 'accent_name' => 'British', 'preview_url' => 'url1'],
            ['id' => 102, 'accent_name' => 'American', 'preview_url' => 'url2'],
        ],
        'category' => 'A',
    ],
    [
        'id' => 2,
        'name' => 'Voice 2',
        'accents' => [
            ['id' => 201, 'accent_name' => 'Australian', 'preview_url' => 'url3'],
        ],
        'category' => 'B',
    ],
    [
        'id' => 3,
        'name' => 'Voice 3',
        'accents' => [], // No accents
        'category' => 'C',
    ],
];
$accentProcessor = new ArrayDataProcessor($accentData);
$accentProcessor->expandByNestedList('accents', [
    'accent_id' => 'id',
    'accent_name' => 'accent_name',
    'preview_url' => 'preview_url',
]);
$accentProcessor->setFields(['id', 'name', 'category', 'accent_id', 'accent_name', 'preview_url']);
echo "\n[Example 1] Expanded by accents (flattened):\n";
print_r($accentProcessor->toArray());
echo "\n[Example 1] Expanded JSON:\n";
echo $accentProcessor->toJson(JSON_PRETTY_PRINT) . "\n";

// --- Example 2: Field casting, aliasing, filtering, sorting, pagination ---
$data = [
    [
        'id' => '1',
        'title' => 'Pro Gaming',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '1200',
        'created_at' => '2025-05-16 08:00:00',
        'tags' => 'fun,live,hd',
        'status' => '1',
        'rating' => '4.5',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => 'HD',
            'meta' => ['lang' => 'en']
        ]),
        'metadata' => json_encode(['foo' => 'bar']),
        'viewer_count' => '1000',
    ],
    [
        'id' => '2',
        'title' => 'Chill Stream',
        'category' => 'Gaming',
        'is_featured' => 'Y',
        'views' => '1200',
        'created_at' => '2025-05-10 10:30:00',
        'tags' => ['relax', 'music'],
        'status' => '0',
        'rating' => '3.8',
        'details' => json_encode([
            'platform' => 'YouTube',
            'quality' => 'SD',
            'meta' => ['lang' => 'fr']
        ]),
        'metadata' => json_encode(['foo' => 'baz']),
        'viewer_count' => '500',
    ],
    [
        'id' => '3',
        'title' => 'Cooking Show',
        'category' => 'Lifestyle',
        'is_featured' => 'N',
        'views' => '1500',
        'created_at' => '2025-05-12 14:00:00',
        'tags' => '',
        'status' => '2',
        'rating' => '4.9',
        'details' => json_encode([
            'platform' => 'Facebook',
            'quality' => 'HD',
            'meta' => ['lang' => 'es']
        ]),
        'metadata' => json_encode(['foo' => 'qux']),
        'viewer_count' => '2000',
    ],
    [
        'id' => '4',
        'title' => 'Tech Review',
        'category' => 'Technology',
        'is_featured' => 'Y',
        'views' => '2000',
        'created_at' => '2025-05-15 09:00:00',
        'tags' => null,
        'status' => '3',
        'rating' => '4.2',
        'details' => json_encode([
            'platform' => 'Twitch',
            'quality' => '4K',
            'meta' => ['lang' => 'en']
        ]),
        'metadata' => json_encode(['foo' => 'zap']),
        'viewer_count' => '1500',
    ],
];
$processor = new ArrayDataProcessor($data);
// Set field types and aliases
$processor
    ->setFieldType('id', 'int')
    ->setFieldType('views', 'int')
    ->setFieldType('created_at', function ($v) {
        return (new DateTime($v))->format('Y-m-d H:i');
    })
    ->setFieldType('details', 'json')
    ->setFieldType('details.platform', 'string')
    ->setFieldType('tags', function ($v) {
        if (is_array($v)) return array_map('trim', $v);
        if (is_string($v) && strlen($v)) return array_map('trim', explode(',', $v));
        return [];
    })
    ->setFieldType('status', function ($v) {
        $map = ['0' => 'inactive', '1' => 'active', '2' => 'suspended', '3' => 'pending'];
        return $map[$v] ?? 'unknown';
    })
    ->setFieldType('rating', function ($v) {
        return number_format((float)$v, 2, '.', '');
    })
    ->setFieldType('metadata', function ($v) {
        $meta = json_decode($v, true);
        $meta['processed_at'] = date('Y-m-d H:i:s');
        return $meta;
    })
    ->setFieldType('viewer_count', function ($v) {
        return (int)$v;
    })
    ->setFieldAlias('views', 'view_count')
    ->setFieldAlias('created_at', 'published_at')
    ->setFieldAlias('details.platform', 'platform')
    ->setFieldAlias('viewer_count', 'audience');
// Select fields
$processor->setFields([
    'id',
    'title',
    'view_count',
    'category',
    'published_at',
    'platform',
    'tags',
    'status',
    'rating',
    'audience',
    'metadata',
]);
// Add filters
$processor->setFilters([
    'is_featured' => ['type' => 'equals', 'value' => 'Y'],
    'category' => ['type' => 'in', 'value' => ['Gaming', 'Technology']],
]);
$processor->setFilterLogic('AND');
// Sort and paginate
$processor
    ->addSortBy('view_count', 'desc')
    ->addSortBy('id', 'asc')
    ->setLimit(3)
    ->setOffset(0);
// Group by a nested field
$groupedByPlatform = $processor->groupBy('platform');
echo "\n[Example 2] Grouped by platform (dot notation):\n";
print_r($groupedByPlatform);
// Output processed data as array

echo "\n[Example 2] Processed array:\n";
print_r($processor->toArray());
// Output as JSON

echo "\n[Example 2] Processed JSON:\n";
echo $processor->toJson(JSON_PRETTY_PRINT) . "\n";
// Output as CSV

echo "\n[Example 2] Processed CSV:\n";
echo $processor->toCsv() . "\n";

// --- Example 3: Array utility methods ---
echo "\n[Example 3] Count: " . $processor->count() . "\n";
echo "[Example 3] First: " . json_encode($processor->first()) . "\n";
echo "[Example 3] Last: " . json_encode($processor->last()) . "\n";
echo "[Example 3] Random: " . json_encode($processor->random()) . "\n";
echo "[Example 3] Reverse: " . json_encode($processor->reverse()) . "\n";
echo "[Example 3] Shuffle: " . json_encode($processor->shuffle()) . "\n";
echo "[Example 3] Pluck titles: " . json_encode($processor->pluck('title')) . "\n";
echo "[Example 3] Filter (view_count > 1000): " . json_encode($processor->filter(function ($row) {
    return $row['view_count'] > 1000;
})) . "\n";
echo "[Example 3] Map (double view_count): " . json_encode($processor->map(function ($row) {
    $row['view_count'] = $row['view_count'] * 2;
    return $row;
})) . "\n";
echo "[Example 3] Sum view_count: " . $processor->sum('view_count') . "\n";
echo "[Example 3] Avg view_count: " . $processor->avg('view_count') . "\n";
echo "[Example 3] Min view_count: " . $processor->min('view_count') . "\n";
echo "[Example 3] Max view_count: " . $processor->max('view_count') . "\n";
// Log of operations
echo "\n[Example 3] Log of operations:\n";
print_r($processor->getLog());

// --- Example 4: Using custom filter callbacks and OR logic ---
$processor->reset();
$processor->setFieldAlias('views', 'view_count'); // Re-apply alias after reset
$processor->setFields(['id', 'title', 'view_count', 'category']);
$processor->addFilter(function ($row) {
    // Custom filter: include if view_count > 1500 or category is Technology
    return ($row['view_count'] > 1500) || ($row['category'] === 'Technology');
}, 'custom_or');
$processor->setFilterLogic('OR');
echo "\n[Example 4] Custom filter (view_count > 1500 OR category = Technology):\n";
print_r($processor->toArray());

// --- Example 5: Enum mapping for status field ---
$processor->reset();
$processor->setEnumMap([
    'status' => [
        'inactive' => 'Inactive',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
    ]
]);
$processor->setFieldType('status', 'enum');
$processor->setFields(['id', 'title', 'status']);
echo "\n[Example 5] Enum mapping for status field:\n";
print_r($processor->toArray());

// --- Example 6: Grouping by multiple fields (manual) ---
$processor->reset();
$processor->setFieldAlias('views', 'view_count'); // Re-apply alias after reset
$processor->setFields(['id', 'title', 'category', 'view_count']);
$grouped = [];
foreach ($processor->toArray() as $row) {
    $key = $row['category'] . '-' . $row['view_count'];
    $grouped[$key][] = $row;
}
echo "\n[Example 6] Grouped by category and view_count (manual):\n";
print_r($grouped);

// --- Example 7: Export CSV with custom delimiter and enclosure ---
$processor->reset();
$processor->setFieldAlias('views', 'view_count'); // Re-apply alias after reset
$processor->setFields(['id', 'title', 'view_count']);
echo "\n[Example 7] CSV export with semicolon delimiter and single quote enclosure:\n";
echo $processor->toCsv(';', "'") . "\n";

// --- Example 8: Get CSV headers only ---
$processor->reset();
$processor->setFieldAlias('views', 'view_count'); // Re-apply alias after reset
$processor->setFields(['id', 'title', 'view_count']);
echo "\n[Example 8] CSV headers only:\n";
print_r($processor->getCsvHeaders());

// --- Example 9: Resetting processor and reusing with new data ---
$newData = [
    ['id' => 10, 'title' => 'New Show', 'view_count' => 123],
    ['id' => 11, 'title' => 'Another Show', 'view_count' => 456],
];
$processor = new ArrayDataProcessor($newData);
$processor->setFields(['id', 'title', 'view_count']);
echo "\n[Example 9] Reused processor with new data:\n";
print_r($processor->toArray());

// --- Example 10: Nested field extraction and dot notation ---
$nestedData = [
    [
        'id' => 1,
        'profile' => [
            'name' => 'Alice',
            'contact' => [
                'email' => 'alice@example.com',
                'phone' => '123-456-7890'
            ]
        ]
    ],
    [
        'id' => 2,
        'profile' => [
            'name' => 'Bob',
            'contact' => [
                'email' => 'bob@example.com',
                'phone' => '555-555-5555'
            ]
        ]
    ]
];
$nestedProcessor = new ArrayDataProcessor($nestedData);
$nestedProcessor->setFields(['id', 'profile.name', 'profile.contact.email']);
echo "\n[Example 10] Nested field extraction (dot notation):\n";
print_r($nestedProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [profile.name] => Alice [profile.contact.email] => alice@example.com )
//     [1] => Array ( [id] => 2 [profile.name] => Bob [profile.contact.email] => bob@example.com )
// )

// --- Example 11: Wildcard extraction for lists ---
$listData = [
    [
        'id' => 1,
        'tags' => ['php', 'arrays', 'data']
    ],
    [
        'id' => 2,
        'tags' => ['json', 'csv']
    ]
];
$listProcessor = new ArrayDataProcessor($listData);
$listProcessor->setFields(['id', 'tags.*']);
echo "\n[Example 11] Wildcard extraction for lists (tags.*):\n";
print_r($listProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [tags.*] => Array ( [0] => php [1] => arrays [2] => data ) )
//     [1] => Array ( [id] => 2 [tags.*] => Array ( [0] => json [1] => csv ) )
// )

// --- Example 12: Handling missing/null fields gracefully ---
$nullData = [
    ['id' => 1, 'name' => 'Test', 'email' => null],
    ['id' => 2, 'name' => 'Demo'],
];
$nullProcessor = new ArrayDataProcessor($nullData);
$nullProcessor->setFields(['id', 'name', 'email']);
echo "\n[Example 12] Handling missing/null fields:\n";
print_r($nullProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [name] => Test [email] =>  )
//     [1] => Array ( [id] => 2 [name] => Demo [email] =>  )
// )

// --- Example 13: Custom type casting with closure (uppercase) ---
$castData = [
    ['id' => 1, 'name' => 'alpha'],
    ['id' => 2, 'name' => 'beta'],
];
$castProcessor = new ArrayDataProcessor($castData);
$castProcessor->setFieldType('name', function ($v) {
    return strtoupper($v);
});
$castProcessor->setFields(['id', 'name']);
echo "\n[Example 13] Custom type casting (uppercase name):\n";
print_r($castProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [name] => ALPHA )
//     [1] => Array ( [id] => 2 [name] => BETA )
// )

// --- Example 14: Pluck utility for nested fields ---
$pluckData = [
    ['id' => 1, 'info' => ['score' => 10]],
    ['id' => 2, 'info' => ['score' => 20]],
];
$pluckProcessor = new ArrayDataProcessor($pluckData);
$pluckProcessor->setFields(['id', 'info.score']);
echo "\n[Example 14] Pluck utility for nested fields (info.score):\n";
print_r($pluckProcessor->pluck('info.score'));
// Result:
// Array ( [0] => 10 [1] => 20 )

// --- Example 15: Using selectKeys to add additional fields dynamically ---
$selectData = [
    ['id' => 1, 'name' => 'Alpha', 'score' => 10, 'extra' => 'foo'],
    ['id' => 2, 'name' => 'Beta', 'score' => 20, 'extra' => 'bar'],
];
$selectProcessor = new ArrayDataProcessor($selectData);
$selectProcessor->setFields(['id', 'name']);
$selectProcessor->selectKeys(['score', 'extra']);
echo "\n[Example 15] selectKeys to add fields dynamically:\n";
print_r($selectProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [name] => Alpha [score] => 10 [extra] => foo )
//     [1] => Array ( [id] => 2 [name] => Beta [score] => 20 [extra] => bar )
// )

// --- Example 16: Using getLastError for error handling ---
$errorProcessor = new ArrayDataProcessor($selectData);
$errorProcessor->setFieldType('', 'int'); // Invalid field name
if ($errorProcessor->getLastError()) {
    echo "\n[Example 16] Error handling with getLastError:\n";
    echo $errorProcessor->getLastError() . "\n";
}
// Result:
// [Example 16] Error handling with getLastError:
// Invalid field name for setFieldType.

// --- Example 17: Using reset to clear configuration but keep data ---
$resetProcessor = new ArrayDataProcessor($selectData);
$resetProcessor->setFields(['id', 'name']);
$resetProcessor->reset();
$resetProcessor->setFields(['id', 'score']);
echo "\n[Example 17] Reset processor and set new fields:\n";
print_r($resetProcessor->toArray());
// Result:
// Array
// (
//     [0] => Array ( [id] => 1 [score] => 10 )
//     [1] => Array ( [id] => 2 [score] => 20 )
// )

// --- Example 18: Using toJson with custom flags (unescaped slashes) ---
$jsonData = [
    ['id' => 1, 'url' => 'https://example.com/foo/bar'],
    ['id' => 2, 'url' => 'https://example.com/baz']
];
$jsonProcessor = new ArrayDataProcessor($jsonData);
$jsonProcessor->setFields(['id', 'url']);
echo "\n[Example 18] toJson with JSON_UNESCAPED_SLASHES:\n";
echo $jsonProcessor->toJson(JSON_UNESCAPED_SLASHES) . "\n";
// Result:
// [{"id":1,"url":"https://example.com/foo/bar"},{"id":2,"url":"https://example.com/baz"}]

// --- Example 19: Using toCsv with empty data ---
$emptyProcessor = new ArrayDataProcessor([]);
echo "\n[Example 19] toCsv with empty data:\n";
echo $emptyProcessor->toCsv() . "\n";
// Result:
// (empty string)

```

---

## API Documentation & Examples

### Constructor

#### `__construct(array $data)`
- **Description:** Create a new processor instance with your data array.
- **Parameters:**
  - `$data` (array): The input data array (array of associative arrays).
- **Example:**
    ```php
    $processor = new ArrayDataProcessor($data);
    ```

---

### Field Type Casting

#### `setFieldType(string $field, string|callable $type)`
- **Description:** Set the type cast for a field. Supports built-in types (`int`, `float`, `string`, `json`, etc.) or a custom closure for advanced logic. Dot notation is supported for nested fields.
- **Parameters:**
  - `$field` (string): Field name (dot notation allowed)
  - `$type` (string|callable): Type or closure
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFieldType('id', 'int');
    $processor->setFieldType('created_at', function ($v) { return (new DateTime($v))->format('Y-m-d H:i'); });
    $processor->setFieldType('details', 'json');
    $processor->setFieldType('tags', function ($v) {
        if (is_array($v)) return array_map('trim', $v);
        if (is_string($v) && strlen($v)) return array_map('trim', explode(',', $v));
        return [];
    });
    ```

---

### Field Aliasing

#### `setFieldAlias(string $field, string $alias)`
- **Description:** Set an alias for a field. Dot notation is supported for nested fields.
- **Parameters:**
  - `$field` (string): Field name (dot notation allowed)
  - `$alias` (string): Alias name
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFieldAlias('views', 'view_count');
    $processor->setFieldAlias('created_at', 'published_at');
    $processor->setFieldAlias('details.platform', 'platform');
    ```

---

### Selecting Output Fields

#### `setFields(array $fields)`
- **Description:** Select only specific fields for output. Dot notation and aliases are supported.
- **Parameters:**
  - `$fields` (array): List of field names or aliases
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFields([
        'id', 'title', 'view_count', 'category', 'published_at', 'platform', 'tags', 'status', 'rating', 'audience', 'metadata',
    ]);
    ```

---

### Filtering

#### `setFilters(array $filterDefinitions)`
- **Description:** Add multiple filters at once. Each filter can specify type (`equals`, `in`, `like`, etc.) and value. Dot notation supported.
- **Parameters:**
  - `$filterDefinitions` (array): Associative array of field => [type, value]
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFilters([
        'is_featured' => ['type' => 'equals', 'value' => 'Y'],
        'category' => ['type' => 'in', 'value' => ['Gaming', 'Technology']],
    ]);
    $processor->setFilterLogic('AND');
    ```

#### `addFilter(callable $callback, string $name = null)`
- **Description:** Add a custom filter callback. Use for advanced or grouped logic.
- **Parameters:**
  - `$callback` (callable): Filter function
  - `$name` (string|null): Optional name
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->addFilter(function ($row) {
        return $row['is_featured'] === 'Y' && $row['category'] === 'Gaming';
    }, 'featured_gaming');
    ```

#### `setFilterLogic(string $logic)`
- **Description:** Set filter logic to 'AND' or 'OR'.
- **Parameters:**
  - `$logic` (string): 'AND' or 'OR'
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setFilterLogic('AND');
    ```

---

### Sorting & Pagination

#### `addSortBy(string $field, string $direction = 'asc')`
- **Description:** Add a sort field. Multi-level sorting supported.
- **Parameters:**
  - `$field` (string): Field name
  - `$direction` (string): 'asc' or 'desc'
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->addSortBy('view_count', 'desc')->addSortBy('id', 'asc');
    ```

#### `setLimit(int $limit)` / `setOffset(int $offset)`
- **Description:** Set result limit and offset for pagination.
- **Parameters:**
  - `$limit` (int): Max results
  - `$offset` (int): Start index
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->setLimit(3)->setOffset(0);
    ```

---

### Grouping

#### `groupBy(string $key)`
- **Description:** Group processed data by a key (dot notation supported).
- **Parameters:**
  - `$key` (string): Field name or alias
- **Returns:** `array` (grouped data)
- **Example:**
    ```php
    $groupedByPlatform = $processor->groupBy('platform');
    print_r($groupedByPlatform);
    ```

---

### Output Methods

#### `toArray()`
- **Description:** Get processed data as array.
- **Returns:** `array`
- **Example:**
    ```php
    print_r($processor->toArray());
    ```

#### `toJson(int $flags = 128)`
- **Description:** Get processed data as JSON.
- **Parameters:**
  - `$flags` (int): JSON encode flags (default: `JSON_PRETTY_PRINT`)
- **Returns:** `string`
- **Example:**
    ```php
    echo $processor->toJson(JSON_PRETTY_PRINT);
    ```

#### `toCsv(string $delimiter = ',', string $enclosure = '"')`
- **Description:** Get processed data as CSV.
- **Parameters:**
  - `$delimiter` (string): CSV delimiter
  - `$enclosure` (string): CSV enclosure
- **Returns:** `string`
- **Example:**
    ```php
    echo $processor->toCsv();
    ```

---

### Array Utility Methods

#### `count()`
- **Description:** Get count of processed records.
- **Returns:** `int`
- **Example:**
    ```php
    echo $processor->count();
    ```

#### `first()` / `last()`
- **Description:** Get first/last record.
- **Returns:** `array|null`
- **Example:**
    ```php
    echo json_encode($processor->first());
    echo json_encode($processor->last());
    ```

#### `random($num = 1)`
- **Description:** Get one or more random records.
- **Parameters:**
  - `$num` (int): Number of records
- **Returns:** `array|mixed|null`
- **Example:**
    ```php
    echo json_encode($processor->random());
    echo json_encode($processor->random(2));
    ```

#### `reverse()` / `shuffle()`
- **Description:** Get reversed/shuffled processed data.
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->reverse());
    echo json_encode($processor->shuffle());
    ```

#### `pluck($key)`
- **Description:** Pluck a single column from processed data.
- **Parameters:**
  - `$key` (string): Field name
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->pluck('title'));
    ```

#### `filter(callable $callback)`
- **Description:** Filter processed data with a callback.
- **Parameters:**
  - `$callback` (callable): Filter function
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->filter(function ($row) {
        return $row['view_count'] > 1000;
    }));
    ```

#### `map(callable $callback)`
- **Description:** Map processed data with a callback.
- **Parameters:**
  - `$callback` (callable): Map function
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->map(function ($row) {
        $row['view_count'] = $row['view_count'] * 2;
        return $row;
    }));
    ```

#### `sum($key)` / `avg($key)` / `min($key)` / `max($key)`
- **Description:** Sum, average, min, or max of a column in processed data.
- **Parameters:**
  - `$key` (string): Field name
- **Returns:** `float|int|mixed`
- **Example:**
    ```php
    echo $processor->sum('view_count');
    echo $processor->avg('view_count');
    echo $processor->min('view_count');
    echo $processor->max('view_count');
    ```

---

### Logging & Utilities

#### `getLog()`
- **Description:** Get a log of all operations performed.
- **Returns:** `array`
- **Example:**
    ```php
    print_r($processor->getLog());
    ```

#### `reset()`
- **Description:** Reset all filters, sorts, aliases, etc. (data remains).
- **Returns:** `$this`
- **Example:**
    ```php
    $processor->reset();
    ```

#### `getCsvHeaders()`
- **Description:** Get the headers for CSV export.
- **Returns:** `array`
- **Example:**
    ```php
    echo json_encode($processor->getCsvHeaders());
    ```

---

### Expanding by Nested List (Flattening Nested Arrays)

#### `expandByNestedList(string $listKey, array $mapping)`
- **Description:** Expand each record by a nested list (e.g., `accents`), duplicating parent fields and mapping nested fields to top-level fields. Produces a flat array with one record per nested item.
- **Parameters:**
  - `$listKey` (string): The key of the nested list to expand (e.g., `'accents'`)
  - `$mapping` (array): Mapping of output field => nested field (e.g., `['accent_name' => 'accent_name', ...]`)
- **Returns:** `$this`
- **Example:**
    ```php
    $accentData = [
        [
            'id' => 1,
            'name' => 'Voice 1',
            'accents' => [
                ['id' => 101, 'accent_name' => 'British', 'preview_url' => 'url1'],
                ['id' => 102, 'accent_name' => 'American', 'preview_url' => 'url2'],
            ],
            'category' => 'A',
        ],
        [
            'id' => 2,
            'name' => 'Voice 2',
            'accents' => [
                ['id' => 201, 'accent_name' => 'Australian', 'preview_url' => 'url3'],
            ],
            'category' => 'B',
        ],
        [
            'id' => 3,
            'name' => 'Voice 3',
            'accents' => [], // No accents
            'category' => 'C',
        ],
    ];

    $accentProcessor = new ArrayDataProcessor($accentData);
    $accentProcessor->expandByNestedList('accents', [
        'accent_id' => 'id',
        'accent_name' => 'accent_name',
        'preview_url' => 'preview_url',
    ]);
    $accentProcessor->setFields(['id', 'name', 'category', 'accent_id', 'accent_name', 'preview_url']);

    // Output the expanded, flattened array
    print_r($accentProcessor->toArray());
    // Output as JSON
    echo $accentProcessor->toJson(JSON_PRETTY_PRINT);
    ```

---

## License
MIT
