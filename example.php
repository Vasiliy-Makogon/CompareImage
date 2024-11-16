<?php

use Krugozor\Hash\AverageHash;

include 'AverageHash.php';

try {
    $hash1 = AverageHash::getHash('1.jpg');
    $hash2 = AverageHash::getHash('2.jpg');

    echo "Difference between $hash1 and $hash2: " . AverageHash::compare($hash1, $hash2);
    echo PHP_EOL;

    $hash3 = AverageHash::getHash('3.jpg');

    echo "Difference between $hash1 and $hash3: " . AverageHash::compare($hash1, $hash3);
    echo PHP_EOL;
} catch (Throwable $t) {
    echo $t->getMessage();
}

