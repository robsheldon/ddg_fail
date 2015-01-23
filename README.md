I love DuckDuckGo but the search results are sometimes a bit lacking.

*analyze_my_search_history.php*
Takes a single argument, the path to your Chrome/Chromium browser history (on Linux/Chromium, this is probably ~/.config/chromium/Default/History), copies the file to /tmp, reads the browser history stored in the file, and returns a list of dates and search terms where you first tried the search with DuckDuckGo and then immediately switched to Google for the same search terms.

Use this file as a template for doing other fun things with your Chrome/Chromium browser history.

You need to have sqlite3 installed on your system.