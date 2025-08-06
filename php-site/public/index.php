<?php
// Load configuration (copy config.sample.php to config.php and set values)
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
} else {
    require_once __DIR__ . '/../includes/config.sample.php';
}

// Handle sample upload
$uploadMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sample'])) {
    $file = $_FILES['sample'];
    if ($file['error'] === 0 && $file['size'] < 10*1024*1024) { // 10MB limit
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $destFolder = __DIR__ . '/../uploads';
        if (!is_dir($destFolder)) mkdir($destFolder, 0775, true);
        $dest = $destFolder . "/sample-" . time() . "." . $ext;
        move_uploaded_file($file['tmp_name'], $dest);
        $uploadMsg = 'Sample uploaded successfully';
    } else {
        $uploadMsg = 'Upload failed (max 10MB)';
    }
}
$initialTheme = (DEFAULT_THEME === 'dark') ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $initialTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voice Clone – Azure TTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body{padding-top:60px;}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1">Voice Clone</span>
    <button id="themeToggle" class="btn btn-outline-secondary">
        <i class="bi bi-moon-fill" id="themeIcon"></i>
    </button>
  </div>
</nav>

<div class="container">
    <?php if ($uploadMsg): ?>
        <div class="alert alert-info mt-2"><?= htmlspecialchars($uploadMsg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <h3>Text to Speech</h3>
            <div class="mb-3">
                <label class="form-label">Text</label>
                <textarea id="synthText" class="form-control" rows="4" placeholder="Type something...">Hello world!</textarea>
            </div>
            <button id="genBtn" class="btn btn-success w-100 mb-3"><i class="bi bi-play-circle"></i> Generate Speech</button>
            <div id="genStatus" class="mb-2"></div>
            <audio id="audioPlayer" controls class="w-100" style="display:none"></audio>
            <a id="downloadLink" class="btn btn-outline-primary w-100 mt-2" href="#" download style="display:none"><i class="bi bi-download"></i> Download</a>
        </div>
        <div class="col-lg-6">
            <h3>Upload Voice Sample (optional)</h3>
            <p class="text-muted small">If you are training a Custom Neural Voice, upload your recorded WAV/MP3 here (max 10&nbsp;MB). Not required for built-in voices.</p>
            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                <input type="file" name="sample" accept="audio/*" class="form-control" required>
                <button class="btn btn-secondary"><i class="bi bi-upload"></i> Upload</button>
            </form>
            <hr>
            <h5 class="mt-4">Current Settings</h5>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">Region<span class="badge bg-primary-subtle text-primary-emphasis"><?= AZURE_REGION ?></span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Voice<span class="badge bg-primary-subtle text-primary-emphasis"><?= AZURE_VOICE ?></span></li>
            </ul>
            <p class="mt-3 small">To change these values edit <code>includes/config.php</code>.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme toggle
(function(){
  const btn = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const doc = document.documentElement;
  const stored = localStorage.getItem('theme');
  if(stored){ doc.setAttribute('data-bs-theme', stored); }
  icon.className = doc.getAttribute('data-bs-theme') === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
  btn.addEventListener('click', () => {
    const current = doc.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    doc.setAttribute('data-bs-theme', next);
    localStorage.setItem('theme', next);
    icon.className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
  });
})();

// Generate speech
const genBtn = document.getElementById('genBtn');
const statusEl = document.getElementById('genStatus');
const audioPlayer = document.getElementById('audioPlayer');
const downloadLink = document.getElementById('downloadLink');

genBtn.addEventListener('click', () => {
  const text = document.getElementById('synthText').value.trim();
  if (!text) return;
  statusEl.textContent = 'Generating...';
  genBtn.disabled = true;
  fetch('synthesize.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text })
  }).then(resp => {
    if (!resp.ok) throw new Error('Failed');
    return resp.blob();
  }).then(blob => {
    const url = URL.createObjectURL(blob);
    audioPlayer.src = url;
    audioPlayer.style.display = 'block';
    downloadLink.href = url;
    downloadLink.download = 'speech.mp3';
    downloadLink.style.display = 'inline-block';
    statusEl.textContent = 'Done';
  }).catch(err => {
    statusEl.textContent = 'Error generating speech';
  }).finally(() => {
    genBtn.disabled = false;
  });
});
</script>
</body>
</html>