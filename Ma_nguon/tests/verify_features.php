<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$stats = $db->getStats();
echo "Driver: " . $stats['driver'] . "\n";
echo "Projects count: " . $stats['projects_count'] . "\n";
echo "Volunteers count: " . $stats['volunteers_count'] . "\n";
$projs = $db->getProjects();
echo "Fetched projects: " . count($projs) . "\n";
$vols = $db->getVolunteers();
echo "Fetched volunteers: " . count($vols) . "\n";

