<?php
require_once 'DB.php';
require_once 'FORM.php';
require_once 'MEMCACHED.php';
require_once 'FILECACHE.php';
require_once 'REDISKO.php';
require_once 'ROUTER.php';
require_once 'IMAGE.php';
require_once 'VISITOR.php';
require_once 'PAGE.php';
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block;');
