# 📊 Vantoria Log Analyzer - PHP Version

Webowa aplikacja do analizy logów serwera Vantoria z interfejsem upload i automatycznym generowaniem raportów.

## 🚀 Jak uruchomić

### Wymagania
- PHP 7.4 lub nowszy
- Serwer web (Apache, Nginx, lub wbudowany serwer PHP)
- Wsparcie dla upload plików

### Metoda 1: Wbudowany serwer PHP (szybki start)

```bash
cd php
php -S localhost:8000
```

Następnie otwórz w przeglądarce: `http://localhost:8000`

### Metoda 2: Apache/Nginx

1. Skopiuj folder `php` do katalogu webowego (np. `/var/www/html/` lub `C:\xampp\htdocs\`)
2. Upewnij się, że PHP ma uprawnienia do zapisu w folderze temp
3. Otwórz w przeglądarce adres serwera

### Metoda 3: XAMPP/WAMP/MAMP

1. Skopiuj folder `php` do folderu `htdocs`
2. Uruchom Apache
3. Otwórz `http://localhost/php/`

## 📁 Struktura plików

```
php/
├── index.php              # Główna strona z formularzem upload
├── analyze.php            # Backend przetwarzający upload i generujący raport
├── LogAnalyzer.php        # Klasa analizująca logi
├── ReportGenerator.php    # Generator raportu HTML
├── .htaccess             # Konfiguracja Apache (opcjonalnie)
└── README.md             # Ten plik
```

## 🎯 Jak używać

1. **Otwórz stronę** - przejdź do `index.php` w przeglądarce
2. **Upload pliku log** - przeciągnij plik lub kliknij aby wybrać (max 50 MB)
3. **Analizuj** - kliknij "Analizuj Log"
4. **Pobierz raport** - raport HTML zostanie automatycznie pobrany

## ✨ Funkcje

### Analiza obejmuje:
- ✅ Podsumowanie statystyk (INFO, WARNING, ERROR)
- ✅ Godzinowy rozkład błędów z wizualizacją
- ✅ Top 30 wiadomości dla każdego poziomu
- ✅ Klikalne wiersze z szczegółami błędów (modal)
- ✅ Stack traces z agregacją
- ✅ Mapowanie IP → Gracze
- ✅ Lista logowań graczy
- ✅ Wyszukiwanie w tabelach
- ✅ Zwijane sekcje
- ✅ Responsive design (Bootstrap 5.3.2)

### Interaktywne funkcje:
- 🔍 Wyszukiwanie w każdej tabeli
- 📋 Klikalne wiersze błędów pokazujące:
  - Pełną treść wiadomości
  - Liczbę wystąpień
  - Do 5 przykładowych wystąpień z timestampami
- 🎨 Gradient design z animacjami
- 📱 Responsywny interfejs

## ⚙️ Konfiguracja

### Limit rozmiaru pliku

W `analyze.php` zmień:
```php
$maxFileSize = 50 * 1024 * 1024; // 50 MB
```

### Timeout i pamięć

W `analyze.php`:
```php
ini_set('max_execution_time', 300); // 5 minut
ini_set('memory_limit', '512M');
```

### PHP.ini (globalnie)

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 512M
```

## 🔒 Bezpieczeństwo

- ✅ Walidacja typów plików (.txt, .log)
- ✅ Limit rozmiaru pliku
- ✅ Pliki tymczasowe z unikalną nazwą
- ✅ Automatyczne czyszczenie plików temp
- ✅ Escape HTML w raportach

### Dodatkowe zabezpieczenia (opcjonalnie)

Dodaj `.htaccess`:
```apache
# Tylko pliki PHP
<FilesMatch "\.(txt|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## 🆚 Różnice vs wersja Python

| Funkcja | Python | PHP |
|---------|--------|-----|
| Uruchomienie | CLI | Web interface |
| Input | Plik z dysku | Upload przez formularz |
| Output | Plik na dysku | Download w przeglądarce |
| Wielowątkowość | ThreadPoolExecutor (8 chunków) | Single-threaded |
| Deployment | Lokalny skrypt | Serwer web |
| UI | Brak | Drag & drop upload |

## 🐛 Rozwiązywanie problemów

### "Błąd podczas uploadu"
- Sprawdź uprawnienia do foldera temp
- Zwiększ `upload_max_filesize` w PHP.ini

### "Timeout"
- Zwiększ `max_execution_time` w PHP.ini
- Zmniejsz plik log lub zwiększ timeout w kodzie

### "Out of memory"
- Zwiększ `memory_limit` w PHP.ini lub pliku analyze.php

## 📊 Wydajność

- Małe pliki (< 1 MB): < 1 sekunda
- Średnie pliki (1-10 MB): 2-5 sekund
- Duże pliki (10-50 MB): 10-30 sekund

## 🎨 Customizacja

### Zmiana kolorów gradientu

W `index.php` i `ReportGenerator.php` znajdź:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

Zastąp kolorami hex według własnych preferencji.

## 📝 Licencja

Ten projekt jest częścią Vantoria Log Analyzer suite.

## 🤝 Wsparcie

W przypadku problemów sprawdź:
1. Logi PHP (error_log)
2. Konsolę przeglądarki (F12)
3. Uprawnienia do katalogów
