<?php
// clean_convert_to_postgres.php

$inputFile = 'database.sql';
$outputFile = 'database_postgres.sql';

if (!file_exists($inputFile)) {
    die("Error: Input file '$inputFile' not found.\n");
}

echo "Reading $inputFile...\n";
$lines = file($inputFile);
$output = [];

$inCreateTable = false;
$buffer = "";

// Header for Postgres.
$output[] = "-- Converted to PostgreSQL on " . date('Y-m-d H:i:s') . "\n";
$output[] = "SET client_encoding = 'UTF8';\n";
$output[] = "SET standard_conforming_strings = on;\n";
$output[] = "\n";

foreach ($lines as $line) {
    $trimLine = trim($line);

    // Skip MySQL-specific comments and commands
    if (
        str_starts_with($trimLine, '/*!') ||
        str_starts_with($trimLine, 'LOCK TABLES') ||
        str_starts_with($trimLine, 'UNLOCK TABLES') ||
        str_starts_with($trimLine, '--') ||
        $trimLine === ''
    ) {
        continue;
    }

    // Handle CREATE TABLE start
    if (str_starts_with($trimLine, 'CREATE TABLE')) {
        $inCreateTable = true;
        // Convert `tablename` to "tablename"
        $line = preg_replace('/`([^`]+)`/', '"$1"', $line);
        $output[] = $line; // <--- This was missing! We need to output the CREATE TABLE line itself.
        continue;
    }

    if ($inCreateTable) {
        // Convert backticks to double quotes for column names
        $line = preg_replace('/`([^`]+)`/', '"$1"', $line);

        // Data Type Conversions
        // ORDER MATTERS! "tinyint", "smallint", "bigint" must be checked BEFORE generic "int"

        // tinyint -> SMALLINT
        $line = preg_replace('/tinyint(?:\(\d+\))?/i', 'SMALLINT', $line);
        // smallint -> SMALLINT
        $line = preg_replace('/smallint(?:\(\d+\))?/i', 'SMALLINT', $line);
        // bigint -> BIGINT
        $line = preg_replace('/bigint(?:\(\d+\))?/i', 'BIGINT', $line);
        // int -> INTEGER (generic, matches int(11) etc)
        $line = preg_replace('/(?<!small|tiny|big)int(?:\(\d+\))?/i', 'INTEGER', $line);

        // AUTO_INCREMENT -> SERIAL
        if (stripos($line, 'AUTO_INCREMENT') !== false) {
            $line = preg_replace('/INTEGER.*AUTO_INCREMENT/i', 'SERIAL', $line);
            $line = str_replace('PRIMARY KEY', '', $line); // Usually inline PK with serial
            // If it was just "id int auto_increment", make it "id" SERIAL
        }

        // ENUMs to VARCHAR (simpler for migration)
        if (stripos($line, 'enum(') !== false) {
            $line = preg_replace("/enum\([^)]+\)/i", "VARCHAR(50)", $line);
        }

        // DATETIME to TIMESTAMP
        $line = preg_replace('/datetime/i', 'TIMESTAMP', $line);

        // Fix current_timestamp() -> CURRENT_TIMESTAMP
        $line = str_replace('current_timestamp()', 'CURRENT_TIMESTAMP', $line);

        // Remove ON UPDATE CURRENT_TIMESTAMP (Postgres uses triggers for this)
        $line = preg_replace('/ON UPDATE CURRENT_TIMESTAMP(?:\(\))?/i', '', $line);

        // Remove MySQL endings
        if (str_ends_with($trimLine, ';')) {
            $inCreateTable = false;
            // Remove engine, charset, etc (everything after the closing parenthesis)
            $line = preg_replace('/\)\s*ENGINE=.*$/i', ');', $line);
        } else {
            // Remove comma from last column if it becomes the last line (not doing complex parsing here, trusting specific structure)
        }

        $output[] = $line;
    }
    // Handle INSERT INTO
    else if (str_starts_with($trimLine, 'INSERT INTO')) {
        // Convert `tablename` to "tablename"
        $line = preg_replace('/`([^`]+)`/', '"$1"', $line);
        // Hex string replacement (0x...) to standard string if needed, but usually SQL dumps use 'string'
        // Escape formatting: MySQL uses \' for quotes, Postgres uses ''
        $line = str_replace("\\'", "''", $line);
        $line = str_replace('\\"', '"', $line); // fix escaped double quotes if any inside strings, though less common in SQL dumps
        $output[] = $line;
    }
    // Handle DROP TABLE
    else if (str_starts_with($trimLine, 'DROP TABLE')) {
        $line = preg_replace('/`([^`]+)`/', '"$1"', $line);
        $output[] = $line;
    }
}

// Post-processing to fix common syntax issues
$finalContent = implode("", $output);

// Fix "id" SERIAL PRIMARY KEY issues if PK was defined separately
// A simple regex to ensure "id" SERIAL doesn't have a trailing comma if it's the only thing on the line? No.
// Let's just write what we have.

// Additional Cleanup
// Remove any lines that are just commas or empty brackets from bad regex
$finalContent = preg_replace('/,\s*\n\);/', "\n);", $finalContent);

// Fix the KEY / INDEX definitions inside CREATE TABLE that might look like keys
// MySQL: KEY `idx_name` (`col`)
// Postgres: CREATE INDEX ... ; (Cannot be inside CREATE TABLE)
// Strip KEY lines from CREATE TABLE
$lines = explode("\n", $finalContent);
// Rethinking the loop strategy. The simpler approach is to parselines again properly.

// NEW LOGIC: Rerun the loop.
$finalLines = [];
$foreignKeys = [];
$currentTable = "";

foreach ($lines as $line) {
    $trim = trim($line);

    if (str_starts_with($trim, 'CREATE TABLE')) {
        if (preg_match('/CREATE TABLE "([^"]+)"/', $trim, $matches)) {
            $currentTable = $matches[1];
        }
    }

    // Handle UNIQUE KEY -> CONSTRAINT UNIQUE
    if (str_starts_with($trim, 'UNIQUE KEY')) {
        $line = preg_replace('/UNIQUE KEY\s+"([^"]+)"\s+\((.+)\)/', 'CONSTRAINT "$1" UNIQUE ($2)', $trim) . "\n";
        $trim = trim($line);
    }

    // Remove KEY lines
    if (str_starts_with($trim, 'KEY') && !str_contains($trim, 'PRIMARY KEY') && !str_contains($trim, 'UNIQUE')) {
        // Remove trailing comma from previous line
        if (!empty($finalLines)) {
            $lastIdx = count($finalLines) - 1;
            $finalLines[$lastIdx] = rtrim($finalLines[$lastIdx], ",\r\n") . "\n";
        }
        continue;
    }

    // Extract FOREIGN KEY
    if (str_contains($trim, 'FOREIGN KEY')) {
        // Remove trailing comma if present on this line for the store
        $fkLine = rtrim($trim, ',');
        $foreignKeys[] = "ALTER TABLE \"$currentTable\" ADD $fkLine;";

        // Remove trailing comma from previous line
        if (!empty($finalLines)) {
            $lastIdx = count($finalLines) - 1;
            $finalLines[$lastIdx] = rtrim($finalLines[$lastIdx], ",\r\n") . "\n";
        }
        continue;
    }

    // Comma check for constraints
    if (str_starts_with($trim, 'CONSTRAINT') || str_starts_with($trim, 'PRIMARY KEY')) {
        if (!empty($finalLines)) {
            $lastIdx = count($finalLines) - 1;
            $lastLine = trim($finalLines[$lastIdx]);
            if (!str_ends_with($lastLine, ',') && !str_ends_with($lastLine, '(')) {
                $finalLines[$lastIdx] = rtrim($finalLines[$lastIdx], "\r\n") . ",\n";
            }
        }
    }

    $finalLines[] = $line; // Use modified line if UNIQUE KEY was hit
}

// Append Foreign Keys at the end
if (!empty($foreignKeys)) {
    $finalLines[] = "\n-- Foreign Keys added at the end to avoid dependency issues\n";
    foreach ($foreignKeys as $fk) {
        $finalLines[] = $fk . "\n";
    }
}

// Write file
if (file_put_contents($outputFile, implode("", $finalLines))) {
    echo "✅ Success! Converted file saved to $outputFile\n";
    echo "Size: " . filesize($outputFile) . " bytes\n";
} else {
    echo "❌ Failed to write output file.\n";
}

?>