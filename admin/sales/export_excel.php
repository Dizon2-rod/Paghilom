<?php require_once dirname(__DIR__).'/includes/db_connect.php'; header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="sales.csv"'); echo "id,code,amount,created_at\n";

