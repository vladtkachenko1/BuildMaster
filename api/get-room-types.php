<?php

require_once '../database/Database.php';
require_once '../Controllers/CalculatorController.php';

use BuildMaster\Controllers\CalculatorController;

$dbInstance = Database::getInstance();
$db = $dbInstance->getConnection();

$controller = new CalculatorController($db);
$controller->getRoomTypes();
