<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['user_id'];

// fetch user row
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// handle theme toggle
if (isset($_GET['toggle_theme'])) {
    $newMode = $user['dark_mode'] ? 0 : 1;
    $db->prepare('UPDATE users SET dark_mode = :m WHERE id = :id')->execute([':m'=>$newMode, ':id'=>$userId]);
    header('Location: dashboard.php');
    exit;
}

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_creds'])) {
        $db->prepare('UPDATE users SET azure_key=:k, azure_region=:r, azure_voice=:v WHERE id=:id')->execute([
            ':k'=>trim($_POST['azure_key'] ?? ''),
            ':r'=>trim($_POST['azure_region'] ?? ''),
            ':v'=>trim($_POST['azure_voice'] ?? ''),
            ':id'=>$userId
        ]);
        $alert = 'Credentials saved';
    } elseif (isset($_POST['upload_sample']) && isset($_FILES['sample'])) {
        $file = $_FILES['sample'];
        if ($file['error'] === 0 && $file['size'] < 10*1024*1024) { // 10MB limit
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $destFolder = __DIR__ . '/../uploads';
            if (!is_dir($destFolder)) mkdir($destFolder, 0775, true);
            $dest = $destFolder . "/sample-{$userId}." . $ext;
            move_uploaded_file($file['tmp_name'], $dest);
            $db->prepare('UPDATE users SET sample_path=:p WHERE id=:id')->execute([':p'=>$dest, ':id'=>$userId]);
            $alert = 'Sample uploaded';
        } else {
            $alert = 'Upload failed';
        }
    }
    // refresh user data
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
$isDark = $user['dark_mode'] ? true : false;
?>
<!DOCTYPE html>
<html lang="en" <?= $isDark? 'data-bs-theme="dark"': '' ?>>
<head>
    <meta charset="UTF-8">
    <title>Dashboard – Voice Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{ padding-top:60px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg <?= $isDark? 'navbar-dark bg-dark':'navbar-light bg-light' ?> fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Voice Clone</a>
    <div class="d-flex">
      <a href="?toggle_theme=1" class="btn btn-outline-secondary me-2">Toggle Theme</a>
      <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
    <?php if ($alert): ?>
        <div class="alert alert-info mt-2"><?= htmlspecialchars($alert) ?></div>
    <?php endif; ?>

    <h3 class="mt-4">Azure Credentials</h3>
    <form method="POST" class="row g-3">
        <input type="hidden" name="save_creds" value="1">
        <div class="col-md-4">
            <label class="form-label">Subscription Key</label>
            <input type="text" name="azure_key" class="form-control" value="<?= htmlspecialchars($user['azure_key'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Region</label>
            <input type="text" name="azure_region" class="form-control" value="<?= htmlspecialchars($user['azure_region'] ?? '') ?>" placeholder="eastus" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Voice Name</label>
            <input type="text" name="azure_voice" class="form-control" value="<?= htmlspecialchars($user['azure_voice'] ?? '') ?>" placeholder="en-US-JennyNeural" required>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>

    <hr class="my-4">

    <h3>Upload sample voice (optional)</h3>
    <?php if ($user['sample_path']): ?>
        <p>Sample uploaded ✔️</p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="upload_sample" value="1">
        <div class="input-group">
            <input type="file" name="sample" accept="audio/*" class="form-control" required>
            <button class="btn btn-secondary">Upload</button>
        </div>
    </form>

    <hr class="my-4">

    <h3>Synthesize Text</h3>
    <div class="mb-3">
        <label class="form-label">Text</label>
        <textarea id="synthText" class="form-control" rows="4" placeholder="Type something...">Hello world!</textarea>
    </div>
    <button id="genBtn" class="btn btn-success">Generate Speech</button>
    <div id="genStatus" class="mt-3"></div>
    <audio id="audioPlayer" controls class="mt-3" style="display:none"></audio>
    <a id="downloadLink" class="btn btn-outline-primary mt-2" href="#" download style="display:none">Download Audio</a>
</div>

<script>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>