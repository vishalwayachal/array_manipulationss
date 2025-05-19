<?php
require_once 'ArrayDataProcessor.php';

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
        'views' => '800',
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
    ->setFieldType('created_at', function ($v) { return (new DateTime($v))->format('Y-m-d H:i'); })
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
    ->setFieldType('rating', function ($v) { return number_format((float)$v, 2, '.', ''); })
    ->setFieldType('metadata', function ($v) {
        $meta = json_decode($v, true);
        $meta['processed_at'] = date('Y-m-d H:i:s');
        return $meta;
    })
    ->setFieldType('viewer_count', function ($v) { return (int)$v; })
    ->setFieldAlias('views', 'view_count')
    ->setFieldAlias('created_at', 'published_at')
    ->setFieldAlias('details.platform', 'platform')
    ->setFieldAlias('viewer_count', 'audience');
// Select fields
$processor->setFields([
    'id', 'title', 'view_count', 'category', 'published_at', 'platform', 'tags', 'status', 'rating', 'audience', 'metadata',
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
$processor->setFields(['id', 'title', 'status']);
$processor->setEnumMap([
    'status' => [
        'inactive' => 'Inactive',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
    ]
]);
$processor->setFieldType('status', 'enum');
echo "\n[Example 5] Enum mapping for status field:\n";
print_r($processor->toArray());

// --- Example 6: Grouping by multiple fields (manual) ---
$processor->reset();
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
$processor->setFields(['id', 'title', 'view_count']);
echo "\n[Example 7] CSV export with semicolon delimiter and single quote enclosure:\n";
echo $processor->toCsv(';', "'") . "\n";

// --- Example 8: Get CSV headers only ---
$processor->reset();
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
$castProcessor->setFieldType('name', function($v) { return strtoupper($v); });
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
