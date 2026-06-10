<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CleatSquad\TextNormalizer\TextNormalizer;

$normalizer = new TextNormalizer();

$datasets = [
    'Latin' => 'Quelle est la MÉTÉO à Rabat aujourd\'hui ? L\'élève de l\'école est très sage.',
    'Arabic' => 'مَا هِيَ حَالَةُ الطَّقْسِ فِي الرِّبَاطِ اليَوْم؟ مَدْرَسَةٌ كَبِيرَةٌ وَجَمِيلَةٌ.',
    'Mixed/Unicode' => 'Météo à Rabat - الطَّقْسُ فِي الرِّبَاط! 2024 🌤️ Košice, Ṣāliḥ & Đà Nẵng!',
];

$iterations = 50_000;

echo sprintf("%-15s | %-12s | %-15s | %-15s\n", "Dataset", "Iterations", "Total Time (ms)", "Ops / sec");
echo str_repeat('-', 65) . "\n";

foreach ($datasets as $name => $text) {
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $normalizer->normalize($text);
    }
    $duration = (microtime(true) - $start);
    $durationMs = $duration * 1000;
    $opsPerSec = $iterations / $duration;

    echo sprintf("%-15s | %-12d | %-15.2f | %-15.0f\n", $name, $iterations, $durationMs, $opsPerSec);
}
