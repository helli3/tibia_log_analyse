<?php

class LogAnalyzer {
    private $logContent;
    private $stats = [];
    private $topMessages = [];
    private $stackTraces = [];
    private $ipPlayers = [];
    private $logins = [];
    private $errorsPerHour = [];
    private $unparsedLines = [];
    private $messagesExamples = [];
    
    public function __construct($logFilePath) {
        $this->logContent = file_get_contents($logFilePath);
        $this->stats = [
            'total_lines' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'other' => 0,
            'logins' => 0,
            'unique_ips' => 0,
            'unique_players' => 0
        ];
    }
    
    public function analyze() {
        $lines = explode("\n", $this->logContent);
        $this->stats['total_lines'] = count($lines);
        
        $messages = [];
        $messagesInfo = [];
        $messagesWarning = [];
        $messagesError = [];
        $stackTracesRaw = [];
        
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Parse timestamp and level
            $parsed = $this->parseLine($line);
            
            if (!$parsed) {
                $this->unparsedLines[] = $line;
                continue;
            }
            
            $timestamp = $parsed['timestamp'];
            $level = $parsed['level'];
            $message = $parsed['message'];
            
            // Update stats
            switch ($level) {
                case 'INFO':
                    $this->stats['info']++;
                    break;
                case 'WARNING':
                    $this->stats['warning']++;
                    break;
                case 'ERROR':
                    $this->stats['error']++;
                    break;
                default:
                    $this->stats['other']++;
            }
            
            // Track errors per hour
            if ($level === 'WARNING' || $level === 'ERROR') {
                $hour = $this->extractHour($timestamp);
                if ($hour) {
                    if (!isset($this->errorsPerHour[$hour])) {
                        $this->errorsPerHour[$hour] = 0;
                    }
                    $this->errorsPerHour[$hour]++;
                }
            }
            
            // Track messages
            $shortMsg = $this->extractMessage($message);
            $key = $level . '|' . $shortMsg;
            
            if (!isset($messages[$key])) {
                $messages[$key] = 0;
            }
            $messages[$key]++;
            
            // Store message examples (max 5 per message)
            if (!isset($this->messagesExamples[$key])) {
                $this->messagesExamples[$key] = [];
            }
            if (count($this->messagesExamples[$key]) < 5) {
                $this->messagesExamples[$key][] = [
                    'timestamp' => $timestamp,
                    'full_text' => substr($line, 0, 500),
                    'level' => $level
                ];
            }
            
            // Categorize by level
            switch ($level) {
                case 'INFO':
                    if (!isset($messagesInfo[$shortMsg])) $messagesInfo[$shortMsg] = 0;
                    $messagesInfo[$shortMsg]++;
                    break;
                case 'WARNING':
                    if (!isset($messagesWarning[$shortMsg])) $messagesWarning[$shortMsg] = 0;
                    $messagesWarning[$shortMsg]++;
                    break;
                case 'ERROR':
                    if (!isset($messagesError[$shortMsg])) $messagesError[$shortMsg] = 0;
                    $messagesError[$shortMsg]++;
                    break;
            }
            
            // Check for stack traces
            if (stripos($message, 'stack trace') !== false || stripos($message, 'error description') !== false) {
                $trace = $this->extractStackTrace($lines, $lineNum);
                if ($trace) {
                    $stackTracesRaw[] = $trace;
                }
            }
            
            // Check for logins
            if (preg_match('/(.+?) logged in/', $message, $matches)) {
                $player = $matches[1];
                $this->stats['logins']++;
                
                if (!isset($this->logins[$player])) {
                    $this->logins[$player] = 0;
                }
                $this->logins[$player]++;
            }
            
            // Extract IP addresses
            if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                $ip = $matches[1];
                if (preg_match('/(.+?) logged in/', $message, $playerMatches)) {
                    $player = $playerMatches[1];
                    if (!isset($this->ipPlayers[$ip])) {
                        $this->ipPlayers[$ip] = [];
                    }
                    if (!in_array($player, $this->ipPlayers[$ip])) {
                        $this->ipPlayers[$ip][] = $player;
                    }
                }
            }
        }
        
        // Sort and prepare top messages
        arsort($messagesInfo);
        arsort($messagesWarning);
        arsort($messagesError);
        
        $this->topMessages['info'] = $this->prepareTopMessages($messagesInfo, 'INFO', 30);
        $this->topMessages['warning'] = $this->prepareTopMessages($messagesWarning, 'WARNING', 30);
        $this->topMessages['error'] = $this->prepareTopMessages($messagesError, 'ERROR', 30);
        
        // Process stack traces
        $this->stackTraces = $this->aggregateStackTraces($stackTracesRaw);
        
        // Calculate unique stats
        $this->stats['unique_ips'] = count($this->ipPlayers);
        $this->stats['unique_players'] = count($this->logins);
        
        // Sort errors per hour
        ksort($this->errorsPerHour);
    }
    
    private function parseLine($line) {
        // Pattern: 2026-02-13_16-32-22.920311 [2026-13-02 16:32:22.920] [error] message
        if (preg_match('/^(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.\d+)\s+\[.*?\]\s+\[(info|warning|error)\]\s+(.*)$/i', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => strtoupper($matches[2]),
                'message' => $matches[3]
            ];
        }
        
        // Alternative pattern
        if (preg_match('/^(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.\d+)\s+(.*)$/i', $line, $matches)) {
            $message = $matches[2];
            $level = 'INFO';
            if (stripos($message, '[warning]') !== false) $level = 'WARNING';
            if (stripos($message, '[error]') !== false) $level = 'ERROR';
            
            return [
                'timestamp' => $matches[1],
                'level' => $level,
                'message' => $message
            ];
        }
        
        return null;
    }
    
    private function extractHour($timestamp) {
        // 2026-02-13_16-32-22.920311 -> 2026-02-13 16:00
        if (preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2})/', $timestamp, $matches)) {
            return $matches[1] . ' ' . $matches[2] . ':00';
        }
        return null;
    }
    
    private function extractMessage($message) {
        // Remove variable parts to group similar messages
        $msg = $message;
        
        // Remove player names in quotes
        $msg = preg_replace('/["\']([^"\']+)["\']/', '...', $msg);
        
        // Remove numbers
        $msg = preg_replace('/\b\d+\.\d+\b/', 'X.X', $msg);
        $msg = preg_replace('/\b\d+\b/', 'X', $msg);
        
        // Truncate long messages
        if (strlen($msg) > 200) {
            $msg = substr($msg, 0, 200);
        }
        
        return trim($msg);
    }
    
    private function extractStackTrace($lines, $startIndex) {
        $trace = '';
        $traceLines = [];
        
        for ($i = $startIndex; $i < min($startIndex + 20, count($lines)); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) break;
            
            $traceLines[] = $line;
            
            // Stop at next timestamp
            if ($i > $startIndex && preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}/', $line)) {
                break;
            }
        }
        
        return implode("\n", $traceLines);
    }
    
    private function aggregateStackTraces($traces) {
        $aggregated = [];
        
        foreach ($traces as $trace) {
            // Extract error type
            $errorType = 'Unknown Error';
            if (preg_match('/Error Description:\s*(.+)$/m', $trace, $matches)) {
                $errorType = trim($matches[1]);
            } elseif (preg_match('/\[error\]\s+(.+)$/m', $trace, $matches)) {
                $errorType = trim($matches[1]);
            }
            
            if (!isset($aggregated[$errorType])) {
                $aggregated[$errorType] = [
                    'count' => 0,
                    'example' => $trace
                ];
            }
            $aggregated[$errorType]['count']++;
        }
        
        uasort($aggregated, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_slice($aggregated, 0, 20, true);
    }
    
    private function prepareTopMessages($messages, $level, $limit) {
        $result = [];
        $count = 0;
        
        foreach ($messages as $msg => $cnt) {
            if ($count >= $limit) break;
            
            $key = $level . '|' . $msg;
            $examples = isset($this->messagesExamples[$key]) ? $this->messagesExamples[$key] : [];
            
            $result[] = [
                'message' => $msg,
                'count' => $cnt,
                'examples' => $examples
            ];
            $count++;
        }
        
        return $result;
    }
    
    public function getStats() {
        return $this->stats;
    }
    
    public function getTopMessages($level = null) {
        if ($level) {
            return isset($this->topMessages[strtolower($level)]) ? $this->topMessages[strtolower($level)] : [];
        }
        return $this->topMessages;
    }
    
    public function getStackTraces() {
        return $this->stackTraces;
    }
    
    public function getIPPlayers() {
        // Sort by number of players
        uasort($this->ipPlayers, function($a, $b) {
            return count($b) - count($a);
        });
        return array_slice($this->ipPlayers, 0, 50, true);
    }
    
    public function getLogins() {
        arsort($this->logins);
        return array_slice($this->logins, 0, 50, true);
    }
    
    public function getErrorsPerHour() {
        return $this->errorsPerHour;
    }
    
    public function getUnparsedLines() {
        return array_slice($this->unparsedLines, 0, 100);
    }
}
