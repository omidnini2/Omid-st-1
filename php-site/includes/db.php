<?php
function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $folder = __DIR__ . '/../data';
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $db = new PDO('sqlite:' . $folder . '/database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // bootstrap schema
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            azure_key TEXT,
            azure_region TEXT,
            azure_voice TEXT,
            dark_mode INTEGER DEFAULT 0,
            sample_path TEXT
        );");
    }
    return $db;
}
?>