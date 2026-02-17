<?php

class ReportGenerator {
    private $analyzer;
    private $stats;
    
    public function __construct(LogAnalyzer $analyzer) {
        $this->analyzer = $analyzer;
        $this->stats = $analyzer->getStats();
    }
    
    public function generate() {
        $timestamp = date('Y-m-d H:i:s');
        
        $html = $this->getHeader($timestamp);
        $html .= $this->getSummarySection();
        $html .= $this->getErrorsPerHourSection();
        $html .= $this->getMessagesSection('INFO');
        $html .= $this->getMessagesSection('WARNING');
        $html .= $this->getMessagesSection('ERROR');
        $html .= $this->getStackTracesSection();
        $html .= $this->getIPPlayersSection();
        $html .= $this->getLoginsSection();
        $html .= $this->getUnparsedSection();
        $html .= $this->getFooter();
        
        return $html;
    }
    
    private function getHeader($timestamp) {
        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vantoria Log Analysis Report - $timestamp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
        }
        .container {
            max-width: 1400px;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .section {
            margin-top: 40px;
            border-radius: 15px;
            overflow: hidden;
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .section-header:hover {
            opacity: 0.9;
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .toggle-icon {
            transition: transform 0.3s;
            font-size: 1.2rem;
        }
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
        .section-content {
            padding: 30px;
            background: #f8f9fa;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .table-wrapper {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            margin-bottom: 0 !important;
        }
        thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .badge {
            font-weight: 600;
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }
        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            border-radius: 50px;
            padding: 10px 20px;
        }
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .clickable-row:hover {
            background-color: #e6f2ff !important;
        }
        .message-cell {
            max-width: 600px;
            word-wrap: break-word;
        }
        .error-bar {
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
            display: inline-block;
            min-width: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Vantoria Log Analysis Report</h1>
        <p class="text-muted">Generated: $timestamp</p>

HTML;
    }
    
    private function getSummarySection() {
        $total = $this->stats['total_lines'];
        $info = $this->stats['info'];
        $warning = $this->stats['warning'];
        $error = $this->stats['error'];
        $logins = $this->stats['logins'];
        $uniqueIPs = $this->stats['unique_ips'];
        $uniquePlayers = $this->stats['unique_players'];
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>📈 Podsumowanie</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">$total</div>
                        <div class="stat-label">Wszystkich linii</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$info</div>
                        <div class="stat-label">INFO</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$warning</div>
                        <div class="stat-label">WARNING</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$error</div>
                        <div class="stat-label">ERROR</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$logins</div>
                        <div class="stat-label">Logowań</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$uniqueIPs</div>
                        <div class="stat-label">Unikalne IP</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">$uniquePlayers</div>
                        <div class="stat-label">Unikalni gracze</div>
                    </div>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getErrorsPerHourSection() {
        $errorsPerHour = $this->analyzer->getErrorsPerHour();
        if (empty($errorsPerHour)) {
            return '';
        }
        
        $totalErrors = array_sum($errorsPerHour);
        $maxErrors = max($errorsPerHour);
        
        $rows = '';
        foreach ($errorsPerHour as $hour => $count) {
            $width = $maxErrors > 0 ? ($count / $maxErrors * 100) : 0;
            $rows .= "<tr>";
            $rows .= "<td><strong>" . htmlspecialchars($hour) . "</strong></td>";
            $rows .= "<td><span class='badge bg-gradient-danger text-white'>{$count}</span></td>";
            $rows .= "<td><div class='error-bar' style='width: {$width}%'></div></td>";
            $rows .= "</tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>⏰ Godzinowy rozkład błędów (WARNING + ERROR) - Razem: $totalErrors</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="table-wrapper">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Godzina</th>
                                <th>Liczba błędów</th>
                                <th>Proporcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getMessagesSection($level) {
        $messages = $this->analyzer->getTopMessages($level);
        if (empty($messages)) {
            return '';
        }
        
        $levelLower = strtolower($level);
        $badgeClass = $level === 'INFO' ? 'info' : ($level === 'WARNING' ? 'warning' : 'danger');
        $emoji = $level === 'INFO' ? 'ℹ️' : ($level === 'WARNING' ? '⚠️' : '❌');
        $count = $this->stats[strtolower($level)];
        
        $rows = '';
        foreach ($messages as $msg) {
            $message = htmlspecialchars($msg['message']);
            $msgCount = $msg['count'];
            $examples = json_encode($msg['examples'], JSON_UNESCAPED_UNICODE);
            
            $rows .= "<tr class='clickable-row' onclick='showErrorDetails(this)' ";
            $rows .= "data-examples='" . htmlspecialchars($examples, ENT_QUOTES) . "' ";
            $rows .= "data-msg='" . htmlspecialchars($message, ENT_QUOTES) . "' ";
            $rows .= "data-count='$msgCount'>";
            $rows .= "<td><span class='badge bg-gradient-{$badgeClass} text-white'>{$msgCount}</span></td>";
            $rows .= "<td class='message-cell'>{$message}</td>";
            $rows .= "</tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>$emoji Top $level Messages - Razem: $count</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="🔍 Szukaj w tabeli..." 
                           onkeyup="filterTable(this, 'table-{$levelLower}')">
                </div>
                <div class="table-wrapper">
                    <table id="table-{$levelLower}" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 100px;">Liczba</th>
                                <th>Wiadomość</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getStackTracesSection() {
        $stackTraces = $this->analyzer->getStackTraces();
        if (empty($stackTraces)) {
            return '';
        }
        
        $rows = '';
        foreach ($stackTraces as $errorType => $data) {
            $errorTypeEsc = htmlspecialchars($errorType);
            $count = $data['count'];
            $example = htmlspecialchars($data['example']);
            
            $rows .= "<tr>";
            $rows .= "<td><span class='badge bg-gradient-danger text-white'>{$count}</span></td>";
            $rows .= "<td>{$errorTypeEsc}</td>";
            $rows .= "<td><button class='btn btn-sm btn-outline-primary' onclick='alert(`" . addslashes($example) . "`)'>Pokaż</button></td>";
            $rows .= "</tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>🐛 Stack Traces</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="🔍 Szukaj w tabeli..." 
                           onkeyup="filterTable(this, 'table-stacktraces')">
                </div>
                <div class="table-wrapper">
                    <table id="table-stacktraces" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 100px;">Liczba</th>
                                <th>Typ błędu</th>
                                <th style="width: 120px;">Przykład</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getIPPlayersSection() {
        $ipPlayers = $this->analyzer->getIPPlayers();
        if (empty($ipPlayers)) {
            return '';
        }
        
        $rows = '';
        foreach ($ipPlayers as $ip => $players) {
            $ipEsc = htmlspecialchars($ip);
            $playerCount = count($players);
            $playersList = htmlspecialchars(implode(', ', $players));
            
            $rows .= "<tr>";
            $rows .= "<td>{$ipEsc}</td>";
            $rows .= "<td><span class='badge bg-gradient-primary text-white'>{$playerCount}</span></td>";
            $rows .= "<td>{$playersList}</td>";
            $rows .= "</tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>🌐 IP → Gracze (Top 50)</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="🔍 Szukaj w tabeli..." 
                           onkeyup="filterTable(this, 'table-ipplayers')">
                </div>
                <div class="table-wrapper">
                    <table id="table-ipplayers" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>IP</th>
                                <th style="width: 100px;">Liczba graczy</th>
                                <th>Gracze</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getLoginsSection() {
        $logins = $this->analyzer->getLogins();
        if (empty($logins)) {
            return '';
        }
        
        $rows = '';
        foreach ($logins as $player => $count) {
            $playerEsc = htmlspecialchars($player);
            
            $rows .= "<tr>";
            $rows .= "<td><span class='badge bg-gradient-primary text-white'>{$count}</span></td>";
            $rows .= "<td>{$playerEsc}</td>";
            $rows .= "</tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>👤 Logowania graczy (Top 50)</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="🔍 Szukaj w tabeli..." 
                           onkeyup="filterTable(this, 'table-logins')">
                </div>
                <div class="table-wrapper">
                    <table id="table-logins" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 100px;">Liczba</th>
                                <th>Gracz</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getUnparsedSection() {
        $unparsed = $this->analyzer->getUnparsedLines();
        if (empty($unparsed)) {
            return '';
        }
        
        $count = count($unparsed);
        $rows = '';
        foreach ($unparsed as $line) {
            $lineEsc = htmlspecialchars($line);
            $rows .= "<tr><td><code>{$lineEsc}</code></td></tr>";
        }
        
        return <<<HTML
        <div class="section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2>❓ Nieparsowane linie (pierwsze $count)</h2>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="section-content">
                <div class="table-wrapper">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Linia</th>
                            </tr>
                        </thead>
                        <tbody>
                            $rows
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

HTML;
    }
    
    private function getFooter() {
        return <<<HTML
        <!-- Modal for error details -->
        <div class='modal fade' id='errorDetailsModal' tabindex='-1' aria-labelledby='errorDetailsModalLabel' aria-hidden='true'>
            <div class='modal-dialog modal-lg modal-dialog-scrollable'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='errorDetailsModalLabel'>📋 Szczegóły błędu</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        <h6>Wiadomość:</h6>
                        <p id='modal-message' class='alert alert-info'></p>
                        
                        <h6>Liczba wystąpień:</h6>
                        <p><span id='modal-count' class='badge bg-primary fs-5'></span></p>
                        
                        <h6>Przykłady wystąpień:</h6>
                        <div id='modal-examples'></div>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Toggle section
        function toggleSection(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.classList.remove('rotated');
            } else {
                content.style.display = 'none';
                icon.classList.add('rotated');
            }
        }

        // Show error details in modal
        function showErrorDetails(row) {
            const msg = row.getAttribute('data-msg');
            const count = row.getAttribute('data-count');
            const examplesJson = row.getAttribute('data-examples');
            
            let examples = [];
            try {
                examples = JSON.parse(examplesJson);
            } catch (e) {
                console.error('Error parsing examples:', e);
            }
            
            // Populate modal
            document.getElementById('modal-message').textContent = msg;
            document.getElementById('modal-count').textContent = count;
            
            const examplesContainer = document.getElementById('modal-examples');
            examplesContainer.innerHTML = '';
            
            if (examples && examples.length > 0) {
                examples.forEach((example, idx) => {
                    const exampleDiv = document.createElement('div');
                    exampleDiv.className = 'card mb-2';
                    exampleDiv.innerHTML = `
                        <div class='card-body'>
                            <h6 class='card-subtitle mb-2 text-muted'>
                                <span class='badge bg-secondary'>Przykład \${idx + 1}</span>
                                <small class='ms-2'>⏰ \${example.timestamp}</small>
                                \${example.level ? `<span class='badge bg-\${example.level === 'ERROR' ? 'danger' : example.level === 'WARNING' ? 'warning' : 'info'} ms-2'>\${example.level}</span>` : ''}
                            </h6>
                            <pre class='mb-0' style='white-space: pre-wrap; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.85rem; max-height: 200px; overflow-y: auto;'>\${example.full_text}</pre>
                        </div>
                    `;
                    examplesContainer.appendChild(exampleDiv);
                });
            } else {
                examplesContainer.innerHTML = '<p class="text-muted">Brak dostępnych przykładów</p>';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('errorDetailsModal'));
            modal.show();
        }

        // Filter table function
        function filterTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.includes(filter) ? '' : 'none';
            }
        }
    </script>
</body>
</html>
HTML;
    }
}
