<?php
// src/Util/VectorUtils.php

namespace App\Util;

class VectorUtils
{
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = array_sum(array_map(fn($a, $b) => $a * $b, $vectorA, $vectorB));
        $magnitudeA = sqrt(array_sum(array_map(fn($a) => $a * $a, $vectorA)));
        $magnitudeB = sqrt(array_sum(array_map(fn($b) => $b * $b, $vectorB)));

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
