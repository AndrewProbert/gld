<?php
// stock_info.php

// Retrieve the symbol from the query parameter
$symbol = 'GLD';

// Fetch stock data from Yahoo Finance API
$url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d";
$data = file_get_contents($url);
$jsonData = json_decode($data, true);

// Check if the necessary data is available
if (isset($jsonData['chart']['result'][0]['meta']['regularMarketPrice'])) {
    $price = $jsonData['chart']['result'][0]['meta']['regularMarketPrice'];
    //echo "Live Price: " . $price;
    //echo "<br>";
}


if (isset($jsonData['chart']['result'][0]['indicators']['quote'][0]['volume'])) {
    $stockVolume = $jsonData['chart']['result'][0]['indicators']['quote'][0]['volume'][0];
    //echo "Stock Volume: " . $stockVolume;
    //echo "<br>";
}
// Function to calculate historical performance score using MACD
function MACD($symbol) {
    // Fetch historical data for the symbol using Yahoo Finance
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=1y";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check if cURL request was successful
    if ($response === false) {
        return null;
    }

    // Close cURL connection
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Extract the desired data (closing prices)
    if (isset($data['chart']['result'][0]['indicators']['quote'][0]['close'])) {
        $closeData = $data['chart']['result'][0]['indicators']['quote'][0]['close'];

        // Calculate the MACD indicator
        $macdData = calculateMACD($closeData);

        // Calculate the score based on the MACD values
        if (!empty($macdData)) {
            $positiveMACDCount = 0;
            foreach ($macdData as $macd) {
                if ($macd > 0) {
                    $positiveMACDCount++;
                }
            }

            // Calculate the score based on the positive MACD values
            $positiveMACDScore = ($positiveMACDCount / count($macdData)) * 100;
            $score = $positiveMACDScore;
            // Cap the score at 100
            $score = min($score, 100);

            return $score;
        }
    }
    echo "Error MACD Function";
    return null;
}

// Function to calculate MACD values
function calculateMACD($data) {
    $ema12 = calculateEMA($data, 12);
    $ema26 = calculateEMA($data, 26);

    $macdLine = array_map(function ($ema12Value, $ema26Value) {
        return $ema12Value - $ema26Value;
    }, $ema12, $ema26);

    return $macdLine;
}

// Function to calculate Exponential Moving Average (EMA)
function calculateEMA($data, $period) {
    $multiplier = 2 / ($period + 1);
    $ema = [];

    // Calculate the initial SMA as the first value
    $sma = array_slice($data, 0, $period);
    $ema[] = array_sum($sma) / $period;

    // Calculate EMA for the remaining values
    for ($i = $period; $i < count($data); $i++) {
        $ema[] = ($data[$i] - $ema[$i - $period]) * $multiplier + $ema[$i - $period];
    }

    return $ema;
}


// Function to calculate price momentum score based on RSI
function RSI($symbol) {
    // Fetch price history for the symbol using Yahoo Finance or your preferred data source
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=1mo";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check if cURL request was successful
    if ($response === false) {
        return null;
    }

    // Close cURL connection
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Extract the desired data (closing prices)
    if (isset($data['chart']['result'][0]['indicators']['quote'][0]['close'])) {
        $closeData = $data['chart']['result'][0]['indicators']['quote'][0]['close'];

        // Calculate the RSI (Relative Strength Index)
        $rsi = calculateRSI($closeData);

        // Calculate the price momentum score based on RSI
        $score = calculateScoreFromRSI($rsi);
        //echo "Price Momentum Score (RSI-based): {$score}\n";
        return $score;
    }

    return null;
}

// Function to calculate RSI (Relative Strength Index)
function calculateRSI($closeData) {
    // Set the time period for RSI calculation
    $timePeriod = 14;

    // Calculate the price changes
    $priceChanges = [];
    $previousPrice = null;
    foreach ($closeData as $price) {
        if ($previousPrice !== null) {
            $priceChange = $price - $previousPrice;
            $priceChanges[] = $priceChange;
        }
        $previousPrice = $price;
    }

    // Calculate the gains and losses
    $gains = [];
    $losses = [];
    foreach ($priceChanges as $priceChange) {
        if ($priceChange > 0) {
            $gains[] = $priceChange;
            $losses[] = 0;
        } else {
            $gains[] = 0;
            $losses[] = abs($priceChange);
        }
    }

    // Calculate the average gains and losses
    $avgGain = array_sum(array_slice($gains, 0, $timePeriod)) / $timePeriod;
    $avgLoss = array_sum(array_slice($losses, 0, $timePeriod)) / $timePeriod;

    // Calculate the initial RSI
    if ($avgLoss == 0) {
        $rsi = 100;
    } else {
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));
    }

    // Calculate the subsequent RSI values
    $dataCount = count($closeData);
    for ($i = $timePeriod; $i < $dataCount; $i++) {
        $priceChange = $priceChanges[$i - 1];
        if ($priceChange > 0) {
            $gain = $priceChange;
            $loss = 0;
        } else {
            $gain = 0;
            $loss = abs($priceChange);
        }

        $avgGain = (($avgGain * ($timePeriod - 1)) + $gain) / $timePeriod;
        $avgLoss = (($avgLoss * ($timePeriod - 1)) + $loss) / $timePeriod;

        if ($avgLoss == 0) {
            $rsiValue = 100;
        } else {
            $rs = $avgGain / $avgLoss;
            $rsiValue = 100 - (100 / (1 + $rs));
        }

        $rsi = round($rsiValue, 2);
    }

    return $rsi;
}

// Function to calculate the score based on RSI
function calculateScoreFromRSI($rsi) {
    // Adjust the RSI score based on a desired scale
    $score = ($rsi - 50) * 2;
    $score = max(min($score, 100), 0);
    return $score;
}


function calculateOBVScore($symbol)
{
    $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    
    // Calculate the date range for three months ago
    $endDate = date("Y-m-d");
    $startDate = date("Y-m-d", strtotime("-3 months", strtotime($endDate)));
    
    // Create the Yahoo Finance API URL with the date range
    $apiUrl .= "?period1=" . strtotime($startDate) . "&period2=" . strtotime($endDate) . "&interval=1d&events=history";
    
    // Fetch data from the Yahoo Finance API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);
    
    // Extract the volume data from the API response
    $volumes = $data['chart']['result'][0]['indicators']['quote'][0]['volume'];
    
    // Calculate the OBV score
    $obvScore = 0;
    $previousVolume = null;
    
    foreach ($volumes as $volume) {
        if ($previousVolume === null) {
            $previousVolume = $volume;
        } else {
            if ($volume > $previousVolume) {
                $obvScore += $volume;
            } elseif ($volume < $previousVolume) {
                $obvScore -= $volume;
            }
            
            $previousVolume = $volume;
        }
    }
    
    // Normalize the OBV score to a range of 0 to 100
    $minVolume = min($volumes);
    $maxVolume = max($volumes);
    $normalizedScore = ($obvScore - $minVolume) / ($maxVolume - $minVolume) * 100;
    
    return $normalizedScore;
}


function calculateADLScore($symbol)
{
    $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    
    // Calculate the date range for three months ago
    $endDate = date("Y-m-d");
    $startDate = date("Y-m-d", strtotime("-3 months", strtotime($endDate)));
    
    // Create the Yahoo Finance API URL with the date range
    $apiUrl .= "?period1=" . strtotime($startDate) . "&period2=" . strtotime($endDate) . "&interval=1d&events=history";
    
    // Fetch data from the Yahoo Finance API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);
    
    // Extract the high, low, close, and volume data from the API response
    $highs = $data['chart']['result'][0]['indicators']['quote'][0]['high'];
    $lows = $data['chart']['result'][0]['indicators']['quote'][0]['low'];
    $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
    $volumes = $data['chart']['result'][0]['indicators']['quote'][0]['volume'];
    
    // Calculate the ADL score
    $adlScore = [];
    $previousADL = 0;
    
    for ($i = 0; $i < count($closes); $i++) {
        $moneyFlowMultiplier = (($closes[$i] - $lows[$i]) - ($highs[$i] - $closes[$i])) / ($highs[$i] - $lows[$i]);
        $moneyFlowVolume = $moneyFlowMultiplier * $volumes[$i];
        
        $adl = $previousADL + $moneyFlowVolume;
        $adlScore[] = $adl;
        $previousADL = $adl;
    }
    
    // Normalize the ADL score to a range of 0 to 100
    $minADL = min($adlScore);
    $maxADL = max($adlScore);
    
    $normalizedScore = [];
    foreach ($adlScore as $adl) {
        $score = ($adl - $minADL) / ($maxADL - $minADL) * 100;
        $normalizedScore[] = $score;
    }
    
    return $normalizedScore;
}
/*
$adlScores = calculateADLScore($symbol);
echo "ADL Scores for $symbol:<br>";
foreach ($adlScores as $score) {
    echo round($score, 2) . "<br>";
}

*/




function calculateADX($symbol)
{
    $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    
    // Calculate the date range for three months ago
    $endDate = date("Y-m-d");
    $startDate = date("Y-m-d", strtotime("-3 months", strtotime($endDate)));
    
    // Create the Yahoo Finance API URL with the date range
    $apiUrl .= "?period1=" . strtotime($startDate) . "&period2=" . strtotime($endDate) . "&interval=1d&events=history";
    
    // Fetch data from the Yahoo Finance API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);
    
    // Extract the high, low, and close data from the API response
    $highs = $data['chart']['result'][0]['indicators']['quote'][0]['high'];
    $lows = $data['chart']['result'][0]['indicators']['quote'][0]['low'];
    $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
    
    // Calculate the True Range (TR)
    $trueRanges = [];
    for ($i = 1; $i < count($closes); $i++) {
        $trueRange = max(
            $highs[$i] - $lows[$i],
            abs($highs[$i] - $closes[$i - 1]),
            abs($lows[$i] - $closes[$i - 1])
        );
        $trueRanges[] = $trueRange;
    }
    
    // Calculate the Directional Movement (DM+ and DM-)
    $dmPlus = [];
    $dmMinus = [];
    for ($i = 1; $i < count($closes); $i++) {
        $upMove = $highs[$i] - $highs[$i - 1];
        $downMove = $lows[$i - 1] - $lows[$i];
        
        if ($upMove > $downMove && $upMove > 0) {
            $dmPlus[] = $upMove;
            $dmMinus[] = 0;
        } elseif ($downMove > $upMove && $downMove > 0) {
            $dmPlus[] = 0;
            $dmMinus[] = $downMove;
        } else {
            $dmPlus[] = 0;
            $dmMinus[] = 0;
        }
    }
    
    // Calculate the Average True Range (ATR)
    $atr = array_sum($trueRanges) / count($trueRanges);
    
    // Calculate the Directional Index (DI+ and DI-)
    $diPlus = (array_sum($dmPlus) / $atr) * 100;
    $diMinus = (array_sum($dmMinus) / $atr) * 100;
    
    // Calculate the Average Directional Index (ADX)
    $adx = (abs($diPlus - $diMinus) / ($diPlus + $diMinus)) * 100;
    
    return $adx;
}


function calculateAroonIndicator($symbol)
{
    $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    
    // Calculate the date range for three months ago
    $endDate = date("Y-m-d");
    $startDate = date("Y-m-d", strtotime("-3 months", strtotime($endDate)));
    
    // Create the Yahoo Finance API URL with the date range
    $apiUrl .= "?period1=" . strtotime($startDate) . "&period2=" . strtotime($endDate) . "&interval=1d&events=history";
    
    // Fetch data from the Yahoo Finance API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);
    
    // Extract the high and low data from the API response
    $highs = $data['chart']['result'][0]['indicators']['quote'][0]['high'];
    $lows = $data['chart']['result'][0]['indicators']['quote'][0]['low'];
    
    // Calculate the Aroon Indicator
    $aroonUp = [];
    $aroonDown = [];
    
    $period = 14; // Aroon Indicator period
    
    for ($i = $period; $i < count($highs); $i++) {
        $highestHighIndex = array_search(max(array_slice($highs, $i - $period, $period + 1)), array_slice($highs, $i - $period, $period + 1));
        $lowestLowIndex = array_search(min(array_slice($lows, $i - $period, $period + 1)), array_slice($lows, $i - $period, $period + 1));
        
        $aroonUp[] = (($period - $highestHighIndex) / $period) * 100;
        $aroonDown[] = (($period - $lowestLowIndex) / $period) * 100;
    }
    
    return array('aroon_up' => $aroonUp, 'aroon_down' => $aroonDown);
}

/*
$aroon = calculateAroonIndicator($symbol);

echo "Aroon Indicator for $symbol:<br>";
for ($i = 0; $i < count($aroon['aroon_up']); $i++) {
    echo "Aroon Up: " . round($aroon['aroon_up'][$i], 2) . "% | Aroon Down: " . round($aroon['aroon_down'][$i], 2) . "%<br>";
}

*/

function calculateStochasticOscillator($symbol)
{
    $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
    
    // Calculate the date range for three months ago
    $endDate = date("Y-m-d");
    $startDate = date("Y-m-d", strtotime("-3 months", strtotime($endDate)));
    
    // Create the Yahoo Finance API URL with the date range
    $apiUrl .= "?period1=" . strtotime($startDate) . "&period2=" . strtotime($endDate) . "&interval=1d&events=history";
    
    // Fetch data from the Yahoo Finance API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);
    
    // Extract the high, low, and close data from the API response
    $highs = $data['chart']['result'][0]['indicators']['quote'][0]['high'];
    $lows = $data['chart']['result'][0]['indicators']['quote'][0]['low'];
    $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
    
    // Calculate the Stochastic Oscillator
    $stochasticOscillator = [];
    
    $period = 14; // Stochastic Oscillator period
    
    for ($i = $period; $i < count($closes); $i++) {
        $highestHigh = max(array_slice($highs, $i - $period + 1, $period));
        $lowestLow = min(array_slice($lows, $i - $period + 1, $period));
        
        $currentClose = $closes[$i];
        
        $stochasticOscillator[] = (($currentClose - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
    }
    
    return $stochasticOscillator;
}

/*

$stochastic = calculateStochasticOscillator($symbol);

echo "Stochastic Oscillator for $symbol:<br>";
foreach ($stochastic as $value) {
    echo round($value, 2) . "<br>";
}


*/

/*

$adx = calculateADX($symbol);
echo "Average Directional Index (ADX): {$adx}\n";





$obvScore = calculateOBVScore($symbol);
echo "On-Balance Volume Score: {$obvScore}\n";

echo "Price Momentum Score (RSI-based): ".RSI($symbol)."\n";
echo "Price Momentum Score (MACD-based): ".MACD($symbol)."\n";



*/


function calculateFinalScore($symbol)
{
    // Calculate individual indicator scores
    $macd = MACD($symbol);
    $rsi = RSI($symbol);
    $obvScore = calculateOBVScore($symbol);
    $adx = calculateADX($symbol);
    $aroon = calculateAroonIndicator($symbol);
    $stochastic = calculateStochasticOscillator($symbol);


    // Calculate average score
    //$averageScore = ($macd + $rsi + $obvScore + $adx + array_sum($aroon['aroon_up']) + array_sum($aroon['aroon_down']) + array_sum($stochastic)) / 5;
    $averageScore = ($macd + $rsi + $obvScore + $adx ) /4;

    // Normalize average score to a range of 0 to 100
    $minScore = 0;
    $maxScore = 100;
    $normalizedScore = ($averageScore - $minScore) / ($maxScore - $minScore) * 100;
    
    return $normalizedScore;
}
echo "Price Momentum Score (RSI-based): ".RSI($symbol)."\n";
echo '<br>';
echo "Price Momentum Score (MACD-based): ".MACD($symbol)."\n";
echo '<br>';

echo "On-Balance Volume Score: ".calculateOBVScore($symbol)."\n";
echo '<br>';

echo "Average Directional Index (ADX): ".calculateADX($symbol)."\n";
echo '<br>';

echo "Final Score: ".calculateFinalScore($symbol)."\n";