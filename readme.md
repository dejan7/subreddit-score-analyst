Subreddit Score Analyst
===================
----------

This is a simple PHP script that retrieves all comments and posts from given subreddit in  given time range. It returns the score for each user who posted in that time period. The data is stored in associative array formatted like this:
```
$users = [
	['username1'] => [
		'commentCount' => 12,
		'commentKarma' => 26,
		'postCount'    => 3,
		'postKarma'    => 222,
		'totalCount'   => 15,
		'totalKarma'   => 248
	],
	['username2'] => ....
]
```

##How to use?
* Set subreddit name: `$subreddit = 'enter-subreddit-name-here';`
* Set date interval: `$dateInterval = "P5D";` 
Date interval sets how far in the past to look for threads. E.G. "P5D" will retrive all threads from the last 5 days and count stats for those threads and each of the thread's comments. 
See this link for more details about the format: http://php.net/manual/en/dateinterval.construct.php

After you've properly configured the script, simply execute the script in terminal
```
$ php ssa.php
```

Currently the data retrieved is outputted to STDOUT (console), but you can easily modify the script and e.g. store all data in a database of your preference.


##How it works?
Reddit JSON API is limitted to 1000 threads and 1000 comments. This means that you can get only the latest 1000 threads. To circumvent the thread limitation, script uses Reddit's CloudSearch, which supports time-range searching so it can fetch any number of threads, from any time range. It iteratively sends requests and retrieves up to 1000 threads for a single day, then it fetches comments for each thread.

##Disclaimer
Due to limitations of Reddit API, this script can be used only for small and medium subreddits. It can fetch up to 1000 threads in a single day and up to 1000 comments on a thread. If threads regularly get over 1000 comments in your subreddit, then this script is not for you, it won't return correct results.


