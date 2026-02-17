<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '512M');

require_once 'LogAnalyzer.php';
require_once 'ReportGenerator.php';

// Check if file was uploaded
if (!isset($_FILES['logfile']) || $_FILES['logfile']['error'] !== UPLOAD_ERR_OK) {
    die('❌ Błąd: Nie przesłano pliku lub wystąpił błąd podczas uploadu.');
}

$uploadedFile = $_FILES['logfile'];
$maxFileSize = 50 * 1024 * 1024; // 50 MB

// Validate file size
if ($uploadedFile['size'] > $maxFileSize) {
    die('❌ Błąd: Plik jest zbyt duży. Maksymalny rozmiar: 50 MB.');
}

// Validate file type
$allowedExtensions = ['txt', 'log'];
$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    die('❌ Błąd: Niedozwolone rozszerzenie pliku. Akceptowane: .txt, .log');
}

// Create temp directory if it doesn't exist
$tempDir = sys_get_temp_dir() . '/hellves_logs';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Move uploaded file to temp location

// Ścieżka do katalogu na kopie uploadowanych plików

// Ścieżka do katalogu na kopie uploadowanych plików (tylko do zapisu, brak dostępu z www)
$uploadsDir = __DIR__ . '/$ave';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    // Dodaj .htaccess blokujący dostęp
    file_put_contents($uploadsDir . '/.htaccess', "Deny from all\n");
}

// Mechanizm deduplication - sprawdź hash pliku
$uploadedFileHash = md5_file($_FILES['logfile']['tmp_name']);
$hashFile = $uploadsDir . '/hashes.txt';
$existingHashes = [];
$hashToReport = [];

if (file_exists($hashFile)) {
    $lines = file($hashFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(',', $line, 2);
        if (count($parts) === 2) {
            $existingHashes[] = $parts[0];
            $hashToReport[$parts[0]] = $parts[1];
        }
    }
}

if (in_array($uploadedFileHash, $existingHashes)) {
    // Plik już był analizowany - wyślij poprzedni raport
    $reportFileName = $hashToReport[$uploadedFileHash];
    $reportFilePath = $uploadsDir . '/' . $reportFileName;
    
    if (file_exists($reportFilePath)) {
        $reportHTML = file_get_contents($reportFilePath);
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $reportFileName . '"');
        header('Content-Length: ' . strlen($reportHTML));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        echo $reportHTML;
        exit;
    }
}

// Nazwa pliku oryginalnego (zabezpieczenie przed kolizją)


// Pobierz IP użytkownika
$userIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown_ip';
$userIpSafe = preg_replace('/[^0-9a-fA-F\-_.]/', '_', str_replace(':', '-', $userIp)); // IPv4/IPv6 safe

// Prosta blokada anty-spam: 1 upload na 30 sekund na IP
$rateLimitFile = $uploadsDir . '/ratelimit_' . $userIpSafe . '.txt';
$now = time();
if (file_exists($rateLimitFile)) {
    $lastUpload = (int)file_get_contents($rateLimitFile);
    if ($now - $lastUpload < 30) {
        die('❌ Zbyt częste próby uploadu. Odczekaj 30 sekund przed kolejną analizą.');
    }
}
file_put_contents($rateLimitFile, $now);

$originalName = basename($uploadedFile['name']);
$uploadTimestamp = date('Ymd_His');
$savedFileName = $uploadTimestamp . '_' . $userIpSafe . '_' . uniqid() . '_' . $originalName;
$savedFilePath = $uploadsDir . '/' . $savedFileName;

// Zapisz kopię oryginalnego pliku do uploads
if (!move_uploaded_file($uploadedFile['tmp_name'], $savedFilePath)) {
    die('❌ Błąd: Nie udało się zapisać pliku.');
}

// Skopiuj do katalogu tymczasowego do analizy (jeśli potrzeba osobnej kopii)
$tempFile = $tempDir . '/' . uniqid('log_') . '.' . $fileExtension;
if (!copy($savedFilePath, $tempFile)) {
    die('❌ Błąd: Nie udało się przygotować pliku do analizy.');
}

try {
    // Analyze log file
    $analyzer = new LogAnalyzer($tempFile);
    $analyzer->analyze();
    
    // Zapisz CSV z danymi graczy do katalogu $ave
    $playerDetails = $analyzer->getPlayerDetails();
    if (!empty($playerDetails)) {
        $csvFileName = $uploadTimestamp . '_' . $userIpSafe . '_players.csv';
        $csvFilePath = $uploadsDir . '/' . $csvFileName;
        
        $csvFile = fopen($csvFilePath, 'w');
        // Header CSV z datą logowania
        fputcsv($csvFile, ['Gracz', 'Poziom', 'Profesja', 'Adres IP', 'Liczba logowań', 'Daty logowań'], ';');
        
        // Sortuj po liczbie logowań (malejąco)
        uasort($playerDetails, function($a, $b) {
            return count($b['logins']) - count($a['logins']);
        });
        
        // Zapisz dane graczy
        foreach ($playerDetails as $player => $details) {
            $loginDates = implode(', ', $details['logins'] ?? []);
            fputcsv($csvFile, [
                $player,
                $details['level'] ?? 'N/A',
                $details['vocation'] ?? 'N/A',
                $details['ip'] ?? 'N/A',
                count($details['logins'] ?? []),
                $loginDates
            ], ';');
        }
        fclose($csvFile);
    }
    
    // Generate HTML report
    $generator = new ReportGenerator($analyzer);
    $reportHTML = $generator->generate();
    
    // Generate filename
    $timestamp = date('Ymd_His');
    $reportFilename = "raport_{$timestamp}.html";
    
    // Zapisz raport HTML do katalogu $ave (dla deduplication)
    $reportFilePath = $uploadsDir . '/' . $reportFilename;
    file_put_contents($reportFilePath, $reportHTML);
    
    // Zapisz hash pliku do listy (deduplication) wraz z nazwą raportu
    file_put_contents($hashFile, $uploadedFileHash . ',' . $reportFilename . "\n", FILE_APPEND);
    
    // Send report as download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $reportFilename . '"');
    header('Content-Length: ' . strlen($reportHTML));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    echo $reportHTML;
    
    // Clean up temp file
    unlink($tempFile);
    
} catch (Exception $e) {
    // Clean up temp file on error
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    die('❌ Błąd podczas analizy: ' . htmlspecialchars($e->getMessage()));
}
