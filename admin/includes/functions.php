<?php
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('money')) { function money($n){ return '₱'.number_format((float)$n,2); } }
?>


