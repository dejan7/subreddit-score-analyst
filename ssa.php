<?php
/**
 * Subreddit Score Analyst
 *
 * simple script that retrieves info about all user scores for a given subreddit in a specified time range
 * data returned is:
 * - comment count per user
 * - post count per user
 * - total (posts + comments) count per user
 * - comment karma per user
 * - post karma per user
 * - total (posts + comments) karma per user
 */


/**
 * CONFIG:
 */
$subreddit = 'enter-subreddit-name-here'; //enter the subreddit name that you want to analyze (WITHOUT /r/ prefix)
$dateInterval = "P5D"; //http://php.net/manual/en/dateinterval.construct.php ; e.g. P5D is 5 days in the past
/**
 * --------------------------------------------------------------
 * Don't edit below this line if you don't know what you're doing
 * --------------------------------------------------------------
 */

$users = [];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1
]);


$dateLimit = new DateTime("@" . time());
//http://php.net/manual/en/dateinterval.construct.php
$dateLimit->sub(new DateInterval($dateInterval));

$postsEndpoint = "https://www.reddit.com/r/".$subreddit."/search.json?sort=new&limit=1000&restrict_sr=on&syntax=cloudsearch";
$commentsEndpoint = 'https://www.reddit.com/r/'.$subreddit.'/comments/';


$totalCommentCount = 0;
$totalPostCount = 0;

$upperLimit = new DateTime("@" . time());
$lowerLimit = new DateTime("@" . time());
$lowerLimit->sub(new DateInterval("P1D"));


while (true) {
    if ($lowerLimit < $dateLimit)
        break; // we went too far in past, stop parsing

    //get posts
    curl_setopt_array($curl, [
        CURLOPT_URL => $postsEndpoint . "&q=timestamp%3A".$lowerLimit->format("U").".." . $upperLimit->format("U")
    ]);

    $postResp = json_decode(curl_exec($curl));

    if (!count($postResp->data->children))
        break; //no more posts

    foreach ($postResp->data->children as $i => $post) {
        $post = $post->data;

        initUser($post->author, $users);
        $users[$post->author]['postCount']++;
        $users[$post->author]['postKarma'] += $post->score;
        $users[$post->author]['totalCount']++;
        $users[$post->author]['totalKarma'] += $post->score;

        //get comments for this post
        curl_setopt_array($curl, [
            CURLOPT_URL => $commentsEndpoint . $post->id .".json?limit=1000"
        ]);

        $commentResp = json_decode(curl_exec($curl));

        foreach ($commentResp[1]->data->children as $comment) {
            $comment = $comment->data;
            parseComment($comment, $users, $totalCommentCount);
        }

        $totalPostCount++;
    }

    $lastPost = $postResp->data->children[count($postResp->data->children)-1];
    $upperLimit->setTimestamp($lastPost->data->created_utc);
    $lowerLimit->setTimestamp($lastPost->data->created_utc);
    $lowerLimit->sub(new DateInterval("P1DT1M"));
}



/**
 * Do something with the fetched data
 * i'm just outputting the data to STDOUT here,
 * however you could do e.g. save the data in a database, write it to a log file....
 */
foreach ($users as $username => $user) {
    fwrite(STDOUT,  $username . " " . $user['totalCount'] . " " . $user['totalKarma'] . "\n");
}


fwrite(STDOUT, "------------------------------\n");
fwrite(STDOUT, "Got: ". $totalCommentCount . " comments! \n");
fwrite(STDOUT, "------------------------------\n");
fwrite(STDOUT, "Got: ". $totalPostCount . " posts! \n");
fwrite(STDOUT, "------------------------------\n");
fwrite(STDOUT, "By: ". count($users) . " users! \n");
fwrite(STDOUT, "------------------------------\n");


curl_close($curl);

/**
 * recursive function to traverse all comment children (comment replies)
 */
function parseComment($comment, &$users, &$cCount) {
    if (!isset($comment->author)) {
        return;
    }

    initUser($comment->author, $users);
    $users[$comment->author]['commentCount']++;
    $users[$comment->author]['commentKarma'] += $comment->score;
    $users[$comment->author]['totalCount']++;
    $users[$comment->author]['totalKarma'] += $comment->score;
    $cCount++;

    if (!$comment->replies)
        return;

    foreach ($comment->replies->data->children as $child) {
        if ($child->data)
            parseComment($child->data, $users, $cCount);
    }
}

/**
 * sets array key with 0s for new user
 */
function initUser($username, &$users) {
    if (!isset($users[$username]))
        $users[$username] = [
            'commentCount' => 0,
            'commentKarma' => 0,
            'postCount'    => 0,
            'postKarma'    => 0,
            'totalCount'   => 0,
            'totalKarma'   => 0
        ];
}