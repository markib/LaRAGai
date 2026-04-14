<?php

if (! function_exists('cosineSimilarity')) {
    function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vectorA as $index => $value) {
            $dot += ($value * ($vectorB[$index] ?? 0));
            $normA += $value * $value;
            $normB += ($vectorB[$index] ?? 0) * ($vectorB[$index] ?? 0);
        }

        return $normA > 0 && $normB > 0 ? $dot / (sqrt($normA) * sqrt($normB)) : 0.0;
    }
}

if (! function_exists('goldenSimilarity')) {
    function goldenSimilarity(string $expected, string $actual): float
    {
        similar_text($expected, $actual, $percent);

        return $percent / 100.0;
    }
}

expect()->extend('toBeVectorLength', function (int $expected) {
    $actual = is_array($this->value) ? count($this->value) : 0;
    expect($actual)->toBe($expected);

    return $this;
});

expect()->extend('toBeCosineSimilarityAtLeast', function (array $other, float $threshold) {
    expect(cosineSimilarity($this->value, $other))->toBeGreaterThanOrEqual($threshold);

    return $this;
});

expect()->extend('toBeGoldenSimilarTo', function (string $expected, float $threshold = 0.7) {
    expect(goldenSimilarity($expected, $this->value))->toBeGreaterThanOrEqual($threshold);

    return $this;
});
