<?php
require_once __DIR__ . '/../lib/bootstrap.php';

//$onlineList = getCurrentlyOnlinePlayers();
$ingameList = getLatestRichPresenceUpdates();

//error_log( count( $onlineList ) );
//error_log( count( $ingameList ) );

$mergedList = [];
//foreach( $onlineList as $playerOnline )
//{
//    $mergedList[ $playerOnline[ 'User' ] ] = $playerOnline;
//    $mergedList[ $playerOnline[ 'User' ] ][ 'InGame' ] = false;
//}

foreach ($ingameList as $playerIngame) {
    //	Array merge/overwrite
    $mergedList[$playerIngame['User']] = $playerIngame;
    $mergedList[$playerIngame['User']]['InGame'] = true;
}

echo json_encode(array_values($mergedList), JSON_UNESCAPED_UNICODE);

// $finalList = array();
// foreach( $mergedList as $mergedItem )
// {
//     $finalList[] = $mergedItem;
// }

//error_log( count( $finalList ) );

// echo json_encode( utf8ize($finalList) );
