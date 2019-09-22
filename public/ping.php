<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$user = seekPOST('u', null);
$gameID = seekPOST('g', null);
$activityMessage = seekPOST('m', null);

if (isset($user)) {
    userActivityPing($user);

    if (isset($gameID) && isset($activityMessage)) {
        UpdateUserRichPresence($user, $gameID, $activityMessage);
    }
}
