<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (getenv('APP_ENV') === 'local') {
    echo '<div>' . getenv('APP_ENV') . '</div>';
    return;
}

$user = seekPOST('u', null);
$gameID = seekPOST('g', null);
$activityMessage = seekPOST('m', null);

if (isset($user)) {
    userActivityPing($user);

    if (isset($gameID) && isset($activityMessage)) {
        UpdateUserRichPresence($user, $gameID, $activityMessage);
    }
}
