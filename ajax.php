<?php
namespace driverok;

include_once 'db_config.php';
include_once 'db.php';

$limit = $_REQUEST['limit'];
$offset = $_REQUEST['offset'];

$db = new Database();

$sql = 'select * from crawler '
    .' limit '.$limit
    .' offset '.$offset;
$results = [];
if ($db->execQuery($sql)) {
    while ($unit = $db->FetchObject()) {
        $results[] = $unit;
    }
}
echo json_encode($results);