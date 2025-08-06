<?php
// ---------------------------------------------
// Copy this file to config.php and fill in your
// Azure Speech credentials and default settings.
// ---------------------------------------------

// Azure subscription key (Speech resource)
define('AZURE_KEY', 'YOUR_SUBSCRIPTION_KEY');

// Azure region (e.g. eastus, westeurope)
define('AZURE_REGION', 'eastus');

// Default voice short name (any built-in Neural voice or your Custom Neural Voice)
// Examples: en-US-JennyNeural, fa-IR-DilaraNeural, my-custom-voice-Neural
define('AZURE_VOICE', 'en-US-JennyNeural');

// Default theme: 'light' or 'dark'
define('DEFAULT_THEME', 'light');
?>