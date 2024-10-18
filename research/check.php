<?php

// Pfad zur Datei 'research.txt'
$commodityFile = 'research.txt';

// Pfad zur Datei 'missing_research.txt'
$missingFile = 'missing_research.txt';

// Überprüfen, ob die Datei 'commodity.txt' existiert
if (!file_exists($commodityFile)) {
    die("Die Datei $commodityFile wurde nicht gefunden.");
}

// Inhalt der Datei 'commodity.txt' lesen
$lines = file($commodityFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$missingEntries = [];

foreach ($lines as $line) {
    // Zeileninhalt nach Tabulator oder Leerzeichen aufteilen
    $parts = preg_split('/\s+/', $line, 2);

    if (count($parts) !== 2) {
        continue;
    }

    list($id, $name) = $parts;

    // Überprüfen, ob die entsprechende PNG-Datei existiert
    if (!file_exists($id . '.png')) {
        $missingEntries[] = "$id\t$name";
    }
}

// Wenn fehlende Einträge vorhanden sind, schreibe sie in 'missing_commodity.txt'
if ($missingEntries) {
    file_put_contents($missingFile, implode(PHP_EOL, $missingEntries));
    echo "Fehlende PNG-Dateien wurden in $missingFile geschrieben.";
} else {
    echo "Alle PNG-Dateien sind vorhanden.";
}
