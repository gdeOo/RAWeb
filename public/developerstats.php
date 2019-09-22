<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$playersOnlineCSV = file_get_contents("./storage/logs/playersonline.log");
$playersCSV = preg_split('/\n|\r\n?/', $playersOnlineCSV);

$staticData = getStaticData();
$errorCode = seekGET('e');
$type = seekGET('t', 0);

RenderDocType();
?>

<head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <?php
    RenderSharedHeader($user);
    RenderTitleTag("Developer Stats", $user);
    RenderGoogleTracking();
    ?>
</head>

<body>

<?php
RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions);
RenderToolbar($user, $permissions);
?>

<div id='mainpage'>

    <div id='leftcontainer'>
        <h3>Developer Stats</h3>
        <?php
        RenderErrorCodeWarning('left', $errorCode);
        $devStatsList = GetDeveloperStatsFull(100, $type);

        echo "<div class='rightfloat'>* = ordered by</div>";
        echo "<table class='smalltable'><tbody>";
        echo "<th>Developer</th>";
        echo "<th>" . ($type == 3 ? "*" : "") . "<a href='/developerstats.php?t=3'>Open Tickets</a></th>";
        echo "<th>" . ($type == 0 ? "*" : "") . "<a href='/developerstats.php?'>Achievements</a></th>";
        echo "<th>" . ($type == 4 ? "*" : "") . "<a href='/developerstats.php?t=4'>Ticket Ratio (%)</a></th>";
        echo "<th>" . ($type == 2 ? "*" : "") . "<a href='/developerstats.php?t=2'>Achievements won by others</a></th>";
        echo "<th>" . ($type == 1 ? "*" : "") . "<a href='/developerstats.php?t=1'>Points awarded to others</a></th>";
        //echo "<th>" . ($type == 5 ? "*" : "") . "<a href='/developerstats.php?t=5'>Last Login</a></th>";

        $userCount = 0;
        foreach ($devStatsList as $devStats) {
            if ($userCount++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class=\"alt\">";
            }

            $dev = $devStats['Author'];
            echo "<td><div class='fixheightcell'>";
            echo GetUserAndTooltipDiv($dev, true);
            echo GetUserAndTooltipDiv($dev, false);
            if ($devStats['Permissions'] < \RA\Permissions::Developer) {
                echo "<br><small>not-a-dev</small>";
            }
            echo "</div></td>";

            echo "<td>" . $devStats['OpenTickets'] . "</td>";
            echo "<td>" . $devStats['Achievements'] . "</td>";
            echo "<td>" . number_format($devStats['TicketRatio'] * 100, 2) . "</td>";
            echo "<td>" . $devStats['ContribCount'] . "</td>";
            echo "<td>" . $devStats['ContribYield'] . "</td>";
            //echo "<td>" . getNiceDate( strtotime( $devStats[ 'LastLogin' ] ) ) . "</td>";
        }
        echo "</tbody></table>";
        ?>
    </div>

    <div id='rightcontainer'>
        <?php
        RenderStaticDataComponent($staticData);
        RenderRecentlyUploadedComponent(10);
        ?>
    </div>

</div>

<?php RenderFooter(); ?>

</body>
</html>
