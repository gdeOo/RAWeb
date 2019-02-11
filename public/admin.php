<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials(
    $user,
    $points,
    $truePoints,
    $unreadMessageCount,
    $permissions,
    \RA\Permissions::Moderator)
) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$newsArticleID = seekGET('n');
$newsCount = getLatestNewsHeaders(0, 999, $newsData);
$activeNewsArticle = null;

$testUser = "Bob";
$rawPass = "qwe";
$saltPass = md5($rawPass . getenv('RA_PASSWORD_SALT'));
$appToken = "INbOEl5bviMEmU4b";

$reqAchievementID = 1;
$reqAchievementValidation = sprintf("%d,%d-%s.%s-%d132%s2A%slLIA", $reqAchievementID, (strlen($testUser) * 3) + 1,
    $testUser, $appToken, $reqAchievementID, $testUser, "WOAHi2");

$awardAchievementID = seekPOST('a');
$awardAchievementUser = seekPOST('u');
$awardAchHardcore = seekPOST('h', 0);

$action = seekPOST('action');
$results = [];
if (!empty($action)) {
    switch ($action) {
        case 'regenapi':
            $query = "SELECT User FROM UserAccounts";
            $dbResult = s_mysql_query($query);
            $userList = '';
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userList[] = $db_entry['User'];
            }
            $numRegens = 0;
            foreach ($userList as $nextTempUser) {
                $newKey = generateAPIKey($nextTempUser);
                if ($newKey !== "") {
                    $numRegens++;
                }
            }
            $results[] = "REGENERATED $numRegens APIKEYS!";
            break;
        case 'regenapione':
            $targetUser = seekGET('t');
            $newKey = generateAPIKey($targetUser);
            $results[] = "New API Key for $targetUser: $newKey";
            break;
        case 'recalcdev':
            $query = "SELECT User FROM UserAccounts";
            $dbResult = s_mysql_query($query);

            $userList = '';
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userList[] = $db_entry['User'];
            }
            $numRegens = 0;
            foreach ($userList as $nextTempUser) {
                $valid = recalculateDevelopmentContributions($nextTempUser);
                $results[] = "$nextTempUser";
                if ($valid) {
                    $numRegens++;
                }
            }

            $results[] = "REGENERATED $numRegens developer contribution totals!";
            break;
        case 'reconstructsiteawards':
            $tgtPlayer = seekGET('t', null);

            $query = "DELETE FROM SiteAwards WHERE AwardType = 1";
            if ($tgtPlayer !== null) {
                $query .= " AND User = '$tgtPlayer'";
            }

            $dbResult = s_mysql_query($query);

            $query = "SELECT User FROM UserAccounts";
            if ($tgtPlayer !== null) {
                $query .= " WHERE User = '$tgtPlayer'";
            }

            $dbResult = s_mysql_query($query);

            $userList = [];
            if ($dbResult !== false) {
                while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                    $userList[] = $db_entry['User'];
                }
            } else {
                $results[] = "Error accessing UserAccounts";
                exit;
            }

            $numAccounts = count($userList);
            for ($i = 0; $i < $numAccounts; $i++) {
                $user = $userList[$i];

                $results[] = "Updating $user...";

                $query = "	SELECT gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon , aw.HardcoreMode
						FROM Awarded AS aw
						LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
						LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
						LEFT JOIN
							( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags=3 GROUP BY GameID )
							AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > 5
						LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
						WHERE aw.User='$user' AND ach.Flags = 3
						GROUP BY ach.GameID, aw.HardcoreMode
						ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title";

                $dbResult = s_mysql_query($query);

                if ($dbResult !== false) {
                    $listOfAwards = [];

                    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                        $listOfAwards[] = $db_entry;
                        //$nextElem = $db_entry;
                        //$nextGameID = $nextElem['GameID'];
                        // if( $nextElem['PctWon'] == 2.0 )
                        // {
                        // $gameTitle = $nextElem['Title'];
                        // $results[]= "Mastered $gameTitle";
                        // //	Add award:
                        // AddSiteAward( $user, 1, $nextElem['GameID'], 1 );
                        // }
                        // if( $nextElem['PctWon'] >= 1.0 )	//noooo!!!!
                        // {
                        // $gameTitle = $nextElem['Title'];
                        // $results[]= "Completed $gameTitle";
                        // //	Add award:
                        // AddSiteAward( $user, 1, $nextElem['GameID'], 0 );
                        // }
                    }

                    $awardAddedHC = [];

                    foreach ($listOfAwards as $nextAward) {
                        if ($nextAward['HardcoreMode'] == 1) {
                            if ($nextAward['PctWon'] == 1.0) {
                                $gameTitle = $nextAward['Title'];
                                $gameID = $nextAward['GameID'];
                                $results[] = "MASTERED $gameTitle";
                                //	Add award:
                                AddSiteAward($user, 1, $gameID, 1);

                                $awardAddedHC[] = $gameID;
                            }
                        }
                    }

                    foreach ($listOfAwards as $nextAward) {
                        if ($nextAward['HardcoreMode'] == 0) {
                            //	Check it hasnt already been added as a non-HC award
                            if ($nextAward['PctWon'] == 1.0) {
                                $gameTitle = $nextAward['Title'];
                                $gameID = $nextAward['GameID'];

                                if (!in_array($gameID, $awardAddedHC)) {
                                    $results[] = "Completed $gameTitle";
                                    //	Add award:
                                    AddSiteAward($user, 1, $gameID, 0);
                                }
                            }
                        }
                    }
                }
            }
            break;
        case 'recalcsiteawards':
            $tgtPlayer = seekGET('t', null);
            {
                $query = "DELETE FROM SiteAwards WHERE ( AwardType = 2 || AwardType = 3 || AwardType = 5 )";
                if ($tgtPlayer !== null) {
                    $query .= " AND User = '$tgtPlayer'";
                }

                global $db;
                $unusedDBResult = mysqli_query($db, $query);
            }
            {
                $query = "SELECT User, ContribCount, ContribYield, fbUser FROM UserAccounts";
                if ($tgtPlayer !== null) {
                    $query .= " WHERE User = '$tgtPlayer'";
                }

                $dbResult = mysqli_query($db, $query);

                $userList = '';
                while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                    $userList[] = [
                        $db_entry['User'],
                        $db_entry['ContribCount'],
                        $db_entry['ContribYield'],
                        $db_entry['fbUser'],
                    ];
                }

                $numRecalced = 0;
                foreach ($userList as $nextTempUser) {
                    global $developerCountBoundaries;
                    global $developerPointBoundaries;

                    $nextUser = $nextTempUser[0];
                    $nextCount = $nextTempUser[1];
                    $nextYield = $nextTempUser[2];
                    $nextFBUser = $nextTempUser[3];

                    for ($i = 0; $i < count($developerCountBoundaries); $i++) {
                        if ($nextCount >= $developerCountBoundaries[$i]) {
                            //$results[]= "$nextUser has $nextCount, greater than $developerCountBoundaries[  $i ], addaward!";
                            //This developer has arrived at this point boundary!
                            AddSiteAward($nextUser, 2, $i);
                            $numRecalced++;
                        }
                    }
                    for ($i = 0; $i < count($developerPointBoundaries); $i++) {
                        if ($nextYield >= $developerPointBoundaries[$i]) {
                            //$results[]= "$nextUser has yield of $nextYield, greater than $developerPointBoundaries[     $i ], addaward!";
                            //This developer is newly above this point boundary!
                            AddSiteAward($nextUser, 3, $i);
                            $numRecalced++;
                        }
                    }

                    if (isset($nextFBUser) && strlen($nextFBUser) > 2) {
                        $results[] = "$nextUser has signed up for FB, add FB award!";
                        AddSiteAward($nextUser, 5, 0);
                    }
                }

                $results[] = "RECALCULATED $numRecalced site awards!";
            }
            break;
        case 'getachids':
            $gameIDs = explode(',', seekPOST('g'));

            foreach ($gameIDs as $nextGameID) {
                $ids = GetAchievementIDs($nextGameID);

                foreach ($ids["AchievementIDs"] as $id) {
                    $results[] = "$id,";
                }
            }
            break;
        case 'giveaward':
            //	Debug award achievement:
            //$awardAchievementID 	= seekPOST( 'a' );
            //$awardAchievementUser = seekPOST( 'u' );
            //$awardAchHardcore 	= seekPOST( 'h', 0 );

            if (isset($awardAchievementID) && isset($awardAchievementUser)) {
                $ids = explode(',', $awardAchievementID);
                foreach ($ids as $nextID) {
                    if (addEarnedAchievement($awardAchievementUser, '', $nextID, 0, $newPointTotal,
                        $awardAchHardcore, false)) {
                        $results[] = " - Updated $awardAchievementUser's score to $newPointTotal!";
                    }
                }
            }
            break;
        case 'recalctrueratio':
            set_time_limit(3000);

            $query = "SELECT MAX(ID) FROM GameData";
            $dbResult = s_mysql_query($query);
            $data = mysqli_fetch_assoc($dbResult);
            $numGames = $data['MAX(ID)'];
            for ($i = 1; $i <= $numGames; $i++) {
                error_log("Recalculating TA for Game ID: $i");
                $results[] = "Recalculating TA for Game ID: $i";
                RecalculateTrueRatio($i);

                ob_flush();
                flush();

                //if( $i % 10 == 0 )
                //	sleep( 1 );
            }

            error_log("Recalc'd TA for $numGames games!");
            $results[] = "Recalc'd TA for $numGames games!";
            exit;
            break;
        case 'recalcplayerscores':
            set_time_limit(3000);

            getUserList(1, 0, 99999, $userData, "");

            $results[] = "Recalc players scores: " . count($userData) . " to process...";

            foreach ($userData as $nextUser) {
                $results[] = "Player: " . $nextUser['User'] . " recalc (was TA: " . $nextUser['TrueRAPoints'] . ")";
                recalcScore($nextUser['User']);

                ob_flush();
                flush();
            }

            error_log("Recalc'd TA for " . count($userData) . " players!");
            $results[] = "Recalc'd TA for " . count($userData) . " players!";
            break;
        case 'updatestaticdata':
            $achID = seekPOSTorGET('a', 0, 'integer');
            $forumID = seekPOSTorGET('f', 0, 'integer');

            //$results[]= $achID;
            //$results[]= $forumID;

            $query = "UPDATE StaticData SET
			Event_AOTW_AchievementID='$achID',
			Event_AOTW_ForumID='$forumID'";

            s_mysql_query($query);
            $results[] = "Successfully updated static data!";
            break;
        case 'access_log':
            // $accessLog = file_get_contents( "../../log/httpd/access_log" );
            // $results[]= $accessLog;
            break;
        case 'error_log':
            // $log = file_get_contents( "../../log/httpd/error_log" );
            // $results[]= $log;
            break;
        case 'errorlog':
            // $errorlogpath = "/var/log/httpd/error_log";
            //$results[]= exec( "tail -n10 $errorlogpath");
            //var_dump( $result );
            // $results[]= "<a href='/admin.php?action=errorlog&c=50'>Last 50</a> - ";
            // $results[]= "<a href='/admin.php?action=errorlog&c=100'>Last 100</a> - ";
            // $results[]= "<a href='/admin.php?action=errorlog&c=500'>Last 500</a>";
            // $count = seekPOSTorGET('c', 20, 'integer');
            // $results[]= nl2br(tailCustom($errorlogpath, $count));
            break;
    }
}

$staticData = getStaticData();
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=windows-1250">
    <title>Administration · RetroAchievements</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="my-3 lead">Administration</h1>

    <?php if (!empty($results)): ?>
        <?php foreach ($results as $result): ?>
            <p class="alert alert-warning">
                <?php echo $result ?>
            </p>
        <?php endforeach ?>
    <?php endif ?>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-md-4">Account:</dt>
                <dd class="col-md-8"><?php echo $user ?></dd>
                <dt class="col-md-4">Permission Role:</dt>
                <dd class="col-md-8"><?php echo PermissionsToString($permissions) ?></dd>
            </dl>
        </div>
    </div>

    <?php if ($permissions >= \RA\Permissions::Moderator): ?>
        <div class="card mb-3">
            <div class="card-header">
                Get Game Achievement IDs
            </div>
            <div class="card-body">
                <form method="post" action="admin.php">
                    <div class="form-group">
                        <label for="">Game ID</label>
                        <input class="form-control" name="g">
                    </div>

                    <input class="form-control" type="hidden" name="action" value="getachids">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Manual Achievement Unlock
            </div>
            <div class="card-body">
                <form method="post" action="admin.php">
                    User To Receive Achievement
                    <input class="form-control" name="u" value="$awardAchievementUser">

                    Achievement ID
                    <input class="form-control" name="a" value="$awardAchievementID">

                    Include hardcore?
                    <input class="form-control" type="checkbox" name="h" <?php ($awardAchHardcore == 1) ? 'checked' : '' ?>>

                    <input class="form-control" type="hidden" name="action" value="giveaward">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($permissions >= \RA\Permissions::Admin): ?>
        <?php
        $eventAchievementID = $staticData['Event_AOTW_AchievementID'];
        $eventForumTopicID = $staticData['Event_AOTW_ForumID'];
        ?>
        <div class="card mb-3">
            <div class="card-header">
            </div>
            <div class="card-body">
            </div>
        </div>
        <h2>Update Event</h2>
        <h3>Achievement of the Week</h3>
        <form method="post" action="admin.php">
            Achievement ID<input class="form-control" name="a" value="$eventAchievementID"> <a
                    href="/Achievement/$eventAchievementID">Link</a>
            Forum Topic ID<input class="form-control" name="f" value="$eventForumTopicID"> <a
                    href="/viewtopic.php?t=$eventForumTopicID">Link</a>
            <input class="form-control" type="hidden" name="action" value="updatestaticdata">
            <button class="btn btn-primary">Submit</button>
        </form>
    <?php endif; ?>

    <?php if ($permissions >= \RA\Permissions::Root): ?>
        <?php /*
        <div class="card mb-3">
            <div class="card-header">
                Request Patch
            </div>
            <div class="card-body">
                <form method=post action="requestpatch.php" enctype="multipart/form-data">
                    User<input class="form-control" name="u" value="<?php echo $user; ?>">
                    Game<input class="form-control" name="g" value="1">
                    Flags<input class="form-control" name="f" value="3">
                    AndLeaderboard<input class="form-control" name="l" value="1">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
        */ ?>

        <div class="card mb-3">
            <div class="card-header">
                Request Leaderboard Entry
            </div>
            <div class="card-body">
                <form method=post action="requestsubmitlbentry.php" enctype="multipart/form-data">
                    User<input class="form-control" name="u" value="<?php echo $user; ?>">
                    Token<input class="form-control" name="t" value="<Apptoken>">
                    LeaderboardID<input class="form-control" name="i" value="1">
                    Validation<input class="form-control" name="v" value="12101020102012">
                    Score<input class="form-control" name="s" value="100">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request Achievement Info
            </div>
            <div class="card-body">
                <form method=post action="requestachievementinfo.php" enctype="multipart/form-data">
                    ID<input class="form-control" name="a" value="1">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <?php /*
        <div class="card mb-3">
            <div class="card-header">
                Request Unlocks FROMAPP
            </div>
            <div class="card-body">
                <form method=post action="requestunlocks.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass<input class="form-control" name="t" value="<?php echo $appToken; ?>">
                    Checksum<input class="form-control" name="c" value="9802">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request All Game Titles
            </div>
            <div class="card-body">
                <form method=post action="requestallgametitles.php">
                    ConsoleID<input class="form-control" 	TYPE="text" name="c" value="1">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request GameID from MD5
            </div>
            <div class="card-body">
                <form method=post action="requestgameid.php">
                    MD5<input class="form-control" name="m" value="">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
        */ ?>

        <div class="card mb-3">
            <div class="card-header">
                Request Achievement FROMAPP
            </div>
            <div class="card-body">
                <form method=post action="requestachievement.php">
                    User <input class="form-control" name="u" value="<?php echo $testUser; ?>" readonly="readonly">
                    AppToken<input class="form-control" name="t" value="<?php echo $appToken; ?>"
                                   readonly="readonly">
                    ID <input class="form-control" name="a" value="<?php echo $reqAchievementID; ?>"
                              readonly="readonly">
                    Val <input class="form-control" name="v" value="<?php echo md5($reqAchievementValidation); ?>"
                               readonly="readonly">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request Submit Alt FROMAPP
            </div>
            <div class="card-body">
                <form METHOD=post ACTION="requestsubmitalt.php">
                    User <input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    AppToken<input class="form-control" name="t" value="<?php echo $appToken; ?>">
                    Checksum<input class="form-control" name="c" value="9802"> (The current game)
                    GameDest<input class="form-control" name="g" value="Sonic The Hedgehog"> (The exact name of the game
                    that
                    already exists)
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request Submit Game Title FROM APP
            </div>
            <div class="card-body">
                <form METHOD=post ACTION="requestsubmitgametitle.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    AppToken<input class="form-control" name="t" value="<?php echo $appToken; ?>">
                    ConsoleID<input class="form-control" name="c" value="1">
                    MD5 of ROM<input class="form-control" name="m" value="">
                    Given Title<input class="form-control" name="g" value="">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <?php /*
        <div class="card mb-3">
            <div class="card-header">
                Request Vote FROM APP
            </div>
            <div class="card-body">
                <form method=post action="requestvote.php">
                    AchievementID<input class="form-control" name="a" value="12">
                    User		 <input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass		 <input class="form-control" name="t" value="<?php echo $appToken; ?>">
                    Vote		 <input class="form-control" name="v" value="1"> -1 for no, 1 for yes
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request Upload Badge
            </div>
            <div class="card-body">
                <form method=post action="requestuploadbadge.php" enctype="multipart/form-data">
                    <label for="file">New badge (64px png pls):</label>
                    <input class="form-control" type="file" name="file">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
        */ ?>

        <h1>Logins</h1>

        <?php /*
        <div class="card mb-3">
            <div class="card-header">
                Request Login FROMAPP
            </div>
            <div class="card-body">
                <form method=post action="requestlogin.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass<input class="form-control" name="p" value="<?php echo $saltPass; ?>">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
        */ ?>

        <div class="card mb-3">
            <div class="card-header">
                Request Login WEBSITE
            </div>
            <div class="card-body">
                <form method=post action="login.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass<input class="form-control" name="p" value="<?php echo $saltPass; ?>">
                    RedirTo<input class="form-control" name="r" value="localhost">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <h1>Messages</h1>

        <div class="card mb-3">
            <div class="card-header">
                Request Message IDs WEBSITE
            </div>
            <div class="card-body">
                <form METHOD=post ACTION="requestmessageids.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass<input class="form-control" name="p" value="<?php echo $saltPass; ?>">
                    Type<input class="form-control" name="x" value="Unread">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request Fetch Message WEBSITE
            </div>
            <div class="card-body">
                <form METHOD=post ACTION="requestfetchmessage.php">
                    User<input class="form-control" name="u" value="<?php echo $testUser; ?>">
                    Pass<input class="form-control" name="p" value="<?php echo $saltPass; ?>">
                    ID<input class="form-control" name="a" value="">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <h1>Accounts</h1>

        <div class="card mb-3">
            <div class="card-header">
                Request Create User
            </div>
            <div class="card-body">
                <form method=post action="requestcreateuser.php">
                    User <input class="form-control" name="u" value="">
                    Pass <input class="form-control" name="p" value="">
                    Email <input class="form-control" name="x" value="">
                    Email2 <input class="form-control" name="y" value="">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Request CodeNotes APP
            </div>
            <div class="card-body">
                <form method=post action="requestcodenotes.php">
                    GameID<input class="form-control" name="g" value="1">
                    <button class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
