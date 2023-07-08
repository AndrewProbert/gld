<!DOCTYPE html>
<html>
<head>
    <title>Gold News</title>
    <style>
        table {
            border-collapse: collapse;
            margin-left: auto;
            margin-right: auto;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
        }
    </style>
    </style>
      <link rel="stylesheet" type="text/css" href="styles.css">

</head>
<body>

<header>
    <nav>
      <div class="logo">
        <a href="index.php"> <img src="gloptionW.png" alt="Logo"></a>
      </div>
      



      
      <ul class="navbar">
        <li><a href="analysis.php">Track</a></li>
        <li><a href="news.php">News</a></li>
        <li><a href="http://localhost/finance/stocks/index.php">Stock Scanner</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </nav>
  </header>
  
    <h1>    <br><br>
Gold News</h1>
    <table>
        <tr>
            <th>Title</th>
            <th>Link</th>
            <th>Timestamp</th>
        </tr>
        <?php
        // Fetch and parse the RSS feed
        $rssFeed = 'https://www.cnbc.com/id/19832390/device/rss/rss.html'; // CNBC Gold News RSS feed
        $xml = simplexml_load_file($rssFeed);

        // Check if the XML was loaded successfully
        if ($xml) {
            // Iterate over the items in the RSS feed
            foreach ($xml->channel->item as $item) {
                $title = $item->title;
                $link = $item->link;
                $timestamp = date('Y-m-d H:i:s', strtotime($item->pubDate));

                // Display the row in the table
                echo "<tr>";
                echo "<td>$title</td>";
                echo "<td><a href='$link'>Read more</a></td>";
                echo "<td>$timestamp</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Failed to load the RSS feed.</td></tr>";
        }
        ?>
    </table>
        <br><br>        <br><br>

    <table>
        <tr>
            <th>Bank</th>
            <th>Title</th>
            <th>Link</th>
            <th>Timestamp</th>
        </tr>
        <?php
        // Array of central bank news feeds
        $feeds = array(
            array(
                'bank' => 'Federal Reserve',
                'url' => 'https://www.federalreserve.gov/feeds/press_all.xml',
            ),
            array(
                'bank' => 'Bank of Canada',
                'url' => 'https://www.bankofcanada.ca/content_type/press-releases/feed/',
            ),
            array(
                'bank' => 'Bank of England',
                'url' => 'https://www.bankofengland.co.uk/rss/news',
            ),
        );

        // Iterate over each central bank news feed
        foreach ($feeds as $feed) {
            $bank = $feed['bank'];
            $rssFeed = $feed['url'];
            $xml = simplexml_load_file($rssFeed);

            // Check if the XML was loaded successfully
            if ($xml) {
                // Iterate over the items in the RSS feed
                foreach ($xml->channel->item as $item) {
                    $title = $item->title;
                    $link = $item->link;
                    $timestamp = date('Y-m-d H:i:s', strtotime($item->pubDate));

                    // Display the row in the table
                    echo "<tr>";
                    echo "<td>$bank</td>";
                    echo "<td>$title</td>";
                    echo "<td><a href='$link'>Read more</a></td>";
                    echo "<td>$timestamp</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>Failed to load the RSS feed for $bank.</td></tr>";
            }
        }
        ?>
    </table>
</body>
</html>
