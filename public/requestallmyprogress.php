<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$user = seekGET('u', null);
$consoleID = seekGET('c', null);

$allProgress = GetAllUserProgress($user, $consoleID);
foreach ($allProgress as $gameID => $nextData) {
    echo $gameID . ":" . $nextData['NumAch'] . ":" . $nextData['Earned'] . ":" . $nextData['HCEarned'] . "\n";
}
