<?php
require_once __DIR__ . '/../config.php';
setCorsHeaders();
$settings = getSettings();
jsonResponse(['success'=>true,'min_amount'=>$settings['min_amount'],'max_amount'=>$settings['max_amount']]);
