<?php

// Function to fetch data from Yahoo Finance API
function fetchData($symbol, $startDate, $endDate)
{
    $url = "https://query1.finance.yahoo.com/v7/finance/download/$symbol?period1=$startDate&period2=$endDate&interval=1d&events=history";
    $data = file_get_contents($url);
    $rows = explode("\n", $data);
    $headers = str_getcsv(array_shift($rows));
    $result = [];

    foreach ($rows as $row) {
        $values = str_getcsv($row);
        if (count($headers) == count($values)) {
            $rowData = array_combine($headers, $values);
            $rowData['Close'] = (float)$rowData['Close']; // Convert Close value to float
            $result[] = $rowData;
        }
    }

    return $result;
}


// Function to calculate RSI (Relative Strength Index)
function calculateRSI($data, $period = 14)
{
    $rsi = [];
    $count = count($data);
    $diffs = array_column($data, 'Close', 'Date');
    $lastClose = null;

    foreach ($diffs as $date => $close) {
        if ($lastClose !== null) {
            $change = $close - $lastClose;
            $diffs[$date] = $change;
        }
        $lastClose = $close;
    }

    $gains = [];
    $losses = [];

    foreach ($diffs as $diff) {
        if ($diff > 0) {
            $gains[] = $diff;
            $losses[] = 0;
        } elseif ($diff < 0) {
            $gains[] = 0;
            $losses[] = abs($diff);
        } else {
            $gains[] = 0;
            $losses[] = 0;
        }
    }

    $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
    $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

    $rsi[] = 100 - (100 / (1 + ($avgGain / $avgLoss)));

    for ($i = $period; $i < $count; $i++) {
        $avgGain = (($avgGain * ($period - 1)) + ($gains[$i])) / $period;
        $avgLoss = (($avgLoss * ($period - 1)) + ($losses[$i])) / $period;
        $rsi[] = 100 - (100 / (1 + ($avgGain / $avgLoss)));
    }

    return $rsi;
}

// Function to calculate SMA (Simple Moving Average)
function calculateSMA($data, $period)
{
    $sma = [];
    $count = count($data);

    for ($i = $period - 1; $i < $count; $i++) {
        $sum = 0;
        for ($j = $i - $period + 1; $j <= $i; $j++) {
            if (isset($data[$j]['Close'])) {
                $sum += $data[$j]['Close'];
            }
        }
        $sma[] = $sum / $period;
    }

    return $sma;
}


// Function to calculate EMA (Exponential Moving Average)
function calculateEMA($data, $period)
{
    $ema = [];
    $count = count($data);
    $multiplier = 2 / ($period + 1);
    $sma = calculateSMA($data, $period);

    for ($i = $period - 1; $i < $count; $i++) {
        $sum = 0;
        for ($j = $i - $period + 1; $j <= $i; $j++) {
            if (isset($data[$j]['Close'])) {
                $sum += $data[$j]['Close'];
            }
        }
        $smaValue = $sum / $period;

        if ($i == $period - 1) {
            $ema[] = $smaValue;
        } else {
            $ema[] = (($data[$i]['Close'] - $ema[$i - $period]) * $multiplier) + $ema[$i - $period];
        }
    }

    return $ema;
}


// Function to calculate MACD (Moving Average Convergence Divergence)
function calculateMACD($data, $shortPeriod = 12, $longPeriod = 26, $signalPeriod = 9)
{
    $macd = [];
    $emaShort = calculateEMA($data, $shortPeriod);
    $emaLong = calculateEMA($data, $longPeriod);

    $count = min(count($emaShort), count($emaLong)); // Use the minimum count of both arrays
    for ($i = 0; $i < $count; $i++) {
        if (isset($emaShort[$i]) && isset($emaLong[$i])) {
            $macd[] = $emaShort[$i] - $emaLong[$i];
        }
    }

    $signalCount = min(count($macd), $signalPeriod); // Use the minimum count of $macd array and $signalPeriod
    $signal = calculateEMA(array_slice($macd, -$signalCount), $signalPeriod);

    return [
        'macd' => $macd,
        'signal' => $signal,
    ];
}



// Function to analyze when to buy call or put options
function analyzeOptions($symbol)
{
    $endDate = strtotime('today');
    $startDate = strtotime('-1 year', $endDate);
    $data = fetchData($symbol, $startDate, $endDate);

    $rsi = calculateRSI($data);
    $sma = calculateSMA($data, 200);
    $ema = calculateEMA($data, 200);
    $macd = calculateMACD($data);

    $lastRSI = end($rsi);
    $lastSMA = end($sma);
    $lastEMA = end($ema);
    $lastMACD = end($macd['macd']);
    $lastSignal = end($macd['signal']);

    $confidenceScore = 0;

    if ($lastRSI > 70 && $lastSMA > $lastEMA && $lastMACD > $lastSignal) {
        $confidenceScore = 100; // High confidence
    } elseif ($lastRSI > 60 && $lastSMA > $lastEMA && $lastMACD > $lastSignal) {
        $confidenceScore = 80; // Medium confidence
    } elseif ($lastRSI > 50 && $lastSMA > $lastEMA && $lastMACD > $lastSignal) {
        $confidenceScore = 60; // Low confidence
    }

    if ($lastRSI < 30 && $lastSMA < $lastEMA && $lastMACD < $lastSignal) {
        $confidenceScore = -100; // High confidence
    } elseif ($lastRSI < 40 && $lastSMA < $lastEMA && $lastMACD < $lastSignal) {
        $confidenceScore = -80; // Medium confidence
    } elseif ($lastRSI < 50 && $lastSMA < $lastEMA && $lastMACD < $lastSignal) {
        $confidenceScore = -60; // Low confidence
    }

    echo "Recommendation for $symbol: ";
    if ($confidenceScore > 0) {
        echo "Buy PUT options. Confidence Score: $confidenceScore";
    } elseif ($confidenceScore < 0) {
        echo "Buy CALL options. Confidence Score: " . abs($confidenceScore);
    } else {
        echo "No clear recommendation at the moment.";
    }

    //In this updated code, a confidence score of 100 indicates a high confidence recommendation, while a score of -100 represents a high confidence recommendation in the opposite direction. 
    //The scores of 80 and 60 denote medium and low confidence, respectively. The scoring system allows for a more granular assessment of the confidence level, ranging from 0 to 100.
}


// Usage
$symbol = 'GLD';
//analyzeOptions($symbol);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Options Analysis</title>
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
        <li><a href="#">Chat</a></li>
        <li><a href="analysis.php">News</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
    </nav>
  </header>
  <div class="content">
    <h1><br><br>Options Analysis</h1>

    <?php
    // Call the analyzeOptions function
    analyzeOptions('GLD');
    ?>

    <!-- Add additional HTML content as needed -->
    </div>
</body>
</html>

