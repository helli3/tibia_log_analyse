<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hellves Log Analyzer - PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .upload-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
            max-width: 600px;
            margin: 0 auto;
        }
        .upload-zone {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 60px 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: #f8f9ff;
        }
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f2ff;
            transform: scale(1.02);
        }
        .upload-zone.dragover {
            border-color: #764ba2;
            background: #e8ebff;
        }
        .upload-icon {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }
        .btn-analyze {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 50px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-analyze:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .file-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }
        .spinner {
            display: none;
        }
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <h1 class="text-center">📊 Hellves Log Analyzer</h1>
            <p class="text-center text-muted mb-4">Upload pliku log i wygeneruj szczegółowy raport</p>
            
            <form id="uploadForm" method="POST" action="analyze.php" enctype="multipart/form-data">
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-icon">📁</div>
                    <h4>Przeciągnij plik lub kliknij aby wybrać</h4>
                    <p class="text-muted">Akceptowane formaty: .txt, .log</p>
                    <input type="file" name="logfile" id="fileInput" accept=".txt,.log" style="display: none;" required>
                </div>
                
                <div class="file-info" id="fileInfo">
                    <strong>Wybrany plik:</strong> <span id="fileName"></span><br>
                    <strong>Rozmiar:</strong> <span id="fileSize"></span>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-analyze" id="analyzeBtn">
                        <span class="btn-text">🚀 Analizuj Log</span>
                        <span class="spinner spinner-border spinner-border-sm" role="status"></span>
                    </button>
                </div>
            </form>
            
            <div class="alert alert-info mt-4">
                <strong>ℹ️ Wskazówki:</strong>
                <ul class="mb-0 mt-2">
                    <li>Maksymalny rozmiar pliku: 50 MB</li>
                    <li>Raport zostanie wygenerowany i automatycznie pobrany</li>
                    <li>Analiza może potrwać kilka sekund w zależności od rozmiaru pliku</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const analyzeBtn = document.getElementById('analyzeBtn');

        // Click to upload
        uploadZone.addEventListener('click', () => fileInput.click());

        // File selected
        fileInput.addEventListener('change', (e) => {
            handleFile(e.target.files[0]);
        });

        // Drag and drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });

        function handleFile(file) {
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                uploadZone.querySelector('h4').textContent = '✓ Plik gotowy do analizy';
                uploadZone.style.borderColor = '#28a745';
                uploadZone.style.background = '#e8ffe8';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Form submission
        uploadForm.addEventListener('submit', (e) => {
            analyzeBtn.querySelector('.btn-text').style.display = 'none';
            analyzeBtn.querySelector('.spinner').style.display = 'inline-block';
            analyzeBtn.disabled = true;

            // Po 2 sekundach sprawdź, czy nie ma błędu (np. blokada uploadu)
            setTimeout(() => {
                // Jeśli strona nie została przekierowana (czyli nie pobrano raportu), przywróć przycisk
                if (!document.hidden) {
                    analyzeBtn.querySelector('.btn-text').style.display = '';
                    analyzeBtn.querySelector('.spinner').style.display = 'none';
                    analyzeBtn.disabled = false;
                }
            }, 2000);
        });
    </script>
</body>
</html>
