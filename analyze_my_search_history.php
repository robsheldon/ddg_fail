<?php

    if ( $argc < 2 ) {
        echo "Usage: " . __FILE__ . " path/to/browser/history/file\n";
        exit;
    }

    //  1. Copy current browser history to a tmp file.
    $history_file = $argv[1];
    if ( ! @file_exists($history_file) ) {
        echo "I can't find the file ${history_file}, giving up.\n";
        exit;
    }
    if ( ! @is_readable($history_file) ) {
        echo "I can find ${history_file} but it isn't readable, giving up.\n";
        exit;
    }
    if ( ! @is_writable('/tmp') ) {
        echo "I can't write to /tmp, giving up.\n";
        exit;
    }
    if ( @file_exists('/tmp/chromium_history') ) {
        //  Probably a screw-up from the last run, let's try nuking it.
        @unlink('/tmp/chromium_history');
    }
    if ( ! @copy($history_file, '/tmp/chromium_history') ) {
        echo "I couldn't copy ${history_file} to /tmp/chromium_history, giving up.\n";
        exit;
    }

    $search_history = array();

    //  2. Get a list of search queries.
    //  Thank you to http://giantdorks.org/alain/export-chrome-or-chromium-browsing-history-on-linux/
    exec("sqlite3 /tmp/chromium_history \"select datetime(last_visit_time/1000000-11644473600,'unixepoch','localtime'),url from urls where url like '%google.com/search%' or url like '%duckduckgo.com%' order by last_visit_time asc\"", $results);

    //  3. Iterate over the results, and...
    foreach ($results as $search) {
        //  Split the result into the data|search_query columns.
        $x = strpos($search, '|');
        $date = substr($search, 0, $x);
        $query = substr($search, $x+1);
        //  Decide whether the engine was DDG or Google,
        //  and then extract the search terms from the query.
        $terms = '';
        if ( preg_match('|^https?://(www.)?(?P<host>[^/]+)|', $query, $re_match) === 1 ) {
            if ( strripos($re_match['host'], 'duckduckgo.com') !== false ) {
                $host = 'duckduckgo.com';
                if ( preg_match('|/\?q=(?P<terms>[^&]+)|', $query, $re_match) === 1 ) {
                    $terms = urldecode($re_match['terms']);
                }
            } else if ( strripos($re_match['host'], 'google.com') !== false ) {
                $host = 'google.com';
                if ( preg_match('|/search\?.*q=(?P<terms>[^&]+)|', $query, $re_match) === 1 ) {
                    $terms = urldecode($re_match['terms']);
                }
            } else {
                $host = 'unknown';
            }
        }
        if ( $terms !== '' ) {
            //  Look at previous search and see if there's a DDG->Google switch.
            if ( $host == 'google.com' && ($x = count($search_history)) > 0 && $search_history[$x-1]['engine'] == 'duckduckgo.com' && $search_history[$x-1]['search'] == $terms ) {
                echo $date . ': ' . $terms . "\n";
            }
            $search_history[] = array('date' => $date, 'engine' => $host, 'search' => $terms);
        }
    }

    //  4. Delete the temporary file.
    if ( ! @unlink('/tmp/chromium_history') ) {
        echo "I couldn't delete /tmp/chromium_history, please delete it for me.\n";
    }

?>
