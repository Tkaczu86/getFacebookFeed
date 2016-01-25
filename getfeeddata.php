<?php

require_once(__DIR__.'/helpers/configs.php');
require_once(__DIR__.'/helpers/api_facebook.php');

// Save feed to database
$fb = new facebookApi($configs);
$feedSave = $fb->saveFeedToDatabase();