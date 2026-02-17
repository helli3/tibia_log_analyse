<?php
/**
 * Test configuration script
 * Sprawdza czy środowisko PHP jest poprawnie skonfigurowane
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>PHP Configuration Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 40px; background: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .check-ok { color: #28a745; }
        .check-warning { color: #ffc107; }
        .check-error { color: #dc3545; }
        h1 { color: #667eea; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Hellves Log Analyzer - Test konfiguracji PHP</h1>
        
        <h3>📊 Informacje o PHP</h3>
        <table class="table table-striped">
            <tr>
                <td><strong>Wersja PHP</strong></td>
                <td><?php echo phpversion(); ?></td>
                <td><?php echo version_compare(phpversion(), '7.4.0', '>=') ? '<span class="check-ok">✓ OK</span>' : '<span class="check-error">✗ Wymagane PHP 7.4+</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>upload_max_filesize</strong></td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                <td><?php 
                    $upload_size = ini_get('upload_max_filesize');
                    $size_mb = intval($upload_size);
                    echo $size_mb >= 50 ? '<span class="check-ok">✓ OK</span>' : '<span class="check-warning">⚠ Zalecane: 50M+</span>'; 
                ?></td>
            </tr>
            <tr>
                <td><strong>post_max_size</strong></td>
                <td><?php echo ini_get('post_max_size'); ?></td>
                <td><?php 
                    $post_size = ini_get('post_max_size');
                    $size_mb = intval($post_size);
                    echo $size_mb >= 50 ? '<span class="check-ok">✓ OK</span>' : '<span class="check-warning">⚠ Zalecane: 50M+</span>'; 
                ?></td>
            </tr>
            <tr>
                <td><strong>max_execution_time</strong></td>
                <td><?php echo ini_get('max_execution_time'); ?> sekund</td>
                <td><?php 
                    $exec_time = ini_get('max_execution_time');
                    echo ($exec_time == 0 || $exec_time >= 300) ? '<span class="check-ok">✓ OK</span>' : '<span class="check-warning">⚠ Zalecane: 300+</span>'; 
                ?></td>
            </tr>
            <tr>
                <td><strong>memory_limit</strong></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
                <td><?php 
                    $memory = ini_get('memory_limit');
                    $size_mb = intval($memory);
                    echo ($memory == '-1' || $size_mb >= 512) ? '<span class="check-ok">✓ OK</span>' : '<span class="check-warning">⚠ Zalecane: 512M+</span>'; 
                ?></td>
            </tr>
        </table>

        <h3>📁 Uprawnienia do zapisu</h3>
        <table class="table table-striped">
            <tr>
                <td><strong>Temp directory</strong></td>
                <td><?php echo sys_get_temp_dir(); ?></td>
                <td><?php echo is_writable(sys_get_temp_dir()) ? '<span class="check-ok">✓ Zapisywalny</span>' : '<span class="check-error">✗ Brak uprawnień do zapisu</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Katalog aplikacji</strong></td>
                <td><?php echo __DIR__; ?></td>
                <td><?php echo is_writable(__DIR__) ? '<span class="check-ok">✓ Zapisywalny</span>' : '<span class="check-warning">⚠ Brak uprawnień (opcjonalne)</span>'; ?></td>
            </tr>
        </table>

        <h3>🔌 Wymagane rozszerzenia PHP</h3>
        <table class="table table-striped">
            <?php
            $required_extensions = ['json', 'mbstring', 'fileinfo'];
            foreach ($required_extensions as $ext) {
                $loaded = extension_loaded($ext);
                echo "<tr>";
                echo "<td><strong>$ext</strong></td>";
                echo "<td>" . ($loaded ? '<span class="check-ok">✓ Załadowane</span>' : '<span class="check-error">✗ Brak</span>') . "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <h3>📦 Pliki aplikacji</h3>
        <table class="table table-striped">
            <?php
            $required_files = ['index.php', 'analyze.php', 'LogAnalyzer.php', 'ReportGenerator.php'];
            foreach ($required_files as $file) {
                $exists = file_exists(__DIR__ . '/' . $file);
                echo "<tr>";
                echo "<td><strong>$file</strong></td>";
                echo "<td>" . ($exists ? '<span class="check-ok">✓ Istnieje</span>' : '<span class="check-error">✗ Brak pliku</span>') . "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <div class="alert alert-info mt-4">
            <strong>ℹ️ Wskazówka:</strong> Jeśli widzisz jakiekolwiek błędy, sprawdź plik <code>README.md</code> 
            aby uzyskać instrukcje konfiguracji.
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary btn-lg">🚀 Przejdź do aplikacji</a>
        </div>

        <hr class="mt-5">
        <details>
            <summary class="text-muted"><small>Pokaż pełną konfigurację PHP (phpinfo)</small></summary>
            <div class="mt-3">
                <?php phpinfo(); ?>
            </div>
        </details>
    </div>
</body>
</html>
