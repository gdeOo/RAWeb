<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$user = seekGET('u', null);

$retVal = [];

$retVal['Score'] = getScore($user);
$retVal['Rank'] = getUserRank($user);

echo json_encode($retVal);
