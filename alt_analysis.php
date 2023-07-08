<?php

$url = "https://query1.finance.yahoo.com/v8/finance/chart/gld?interval=1m";




// A function to calculate the on balance volume (OBV) for the gld etf
function obv($url) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps, close prices and volumes from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"];
    $volume = $data["chart"]["result"][0]["indicators"]["quote"][0]["volume"];
    // Initialize the obv and the previous close price
    $obv = 0;
    $prev_close = $close[0];
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // If the current close price is higher than the previous one, add the volume to the obv
      if ($close[$i] > $prev_close) {
        $obv += $volume[$i];
      }
      // If the current close price is lower than the previous one, subtract the volume from the obv
      elseif ($close[$i] < $prev_close) {
        $obv -= $volume[$i];
      }
      // If the current close price is equal to the previous one, do nothing
      else {
        // Do nothing
      }
      // Update the previous close price
      $prev_close = $close[$i];
    }
    // Normalize the obv to a score between -100 and 100
    $max_obv = max($volume) * count($timestamps);
    $min_obv = -max($volume) * count($timestamps);
    $score = ($obv - $min_obv) / ($max_obv - $min_obv) * 200 - 100;
    // Return the score
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo $score;
    return $score;
  }
  
  
  // A function to calculate the accumulation/distribution (AD) for the gld etf
  function ad($url) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps, high, low, close and volume from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $high = $data["chart"]["result"][0]["indicators"]["quote"][0]["high"];
    $low = $data["chart"]["result"][0]["indicators"]["quote"][0]["low"];
    $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"];
    $volume = $data["chart"]["result"][0]["indicators"]["quote"][0]["volume"];
    // Initialize the ad and the previous close price
    $ad = 0;
    $prev_close = $close[0];
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // Calculate the money flow multiplier
      $mfm = ($close[$i] - $low[$i]) - ($high[$i] - $close[$i]);
      if ($high[$i] != $low[$i]) {
        $mfm /= ($high[$i] - $low[$i]);
      }
      else {
        $mfm = 0;
      }
      // Calculate the money flow volume
      $mfv = $mfm * $volume[$i];
      // Add the money flow volume to the ad
      $ad += $mfv;
      // Update the previous close price
      $prev_close = $close[$i];
    }
    // Normalize the ad to a score between -100 and 100
    $max_ad = max($volume) * count($timestamps);
    $min_ad = -max($volume) * count($timestamps);
    $score = ($ad - $min_ad) / ($max_ad - $min_ad) * 200 - 100;
    // Return the score
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo $score;
    return $score;
  }

  // A function to calculate the average directional index (ADX) for the gld etf
function adx($url, $period) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps, high, low and close from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $high = $data["chart"]["result"][0]["indicators"]["quote"][0]["high"];
    $low = $data["chart"]["result"][0]["indicators"]["quote"][0]["low"];
    $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"];
    // Initialize the arrays for true range, directional movement and smoothed averages
    $tr = array();
    $dm_plus = array();
    $dm_minus = array();
    $atr = array();
    $pdi = array();
    $mdi = array();
    $dx = array();
    $adx = array();
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // Calculate the true range
      if ($i == 0) {
        // For the first point, use the high-low range
        $tr[$i] = $high[$i] - $low[$i];
      }
      else {
        // For the subsequent points, use the maximum of three values
        $tr[$i] = max($high[$i] - $low[$i], abs($high[$i] - $close[$i - 1]), abs($low[$i] - $close[$i - 1]));
      }
      // Calculate the directional movement
      if ($i > 0) {
        // For the subsequent points, use the difference between highs and lows
        $dm_plus[$i] = max($high[$i] - $high[$i - 1], 0);
        $dm_minus[$i] = max($low[$i - 1] - $low[$i], 0);
        // If both are positive, take the larger one and set the other to zero
        if ($dm_plus[$i] > 0 && $dm_minus[$i] > 0) {
          if ($dm_plus[$i] > $dm_minus[$i]) {
            $dm_minus[$i] = 0;
          }
          else {
            $dm_plus[$i] = 0;
          }
        }
      }
      // Calculate the smoothed averages
      if ($i >= $period - 1) {
        // For the points after the period, use the exponential moving average formula
        if ($i == $period - 1) {
          // For the first point, use the simple average of the initial period
          $atr[$i] = array_sum(array_slice($tr, 0, $period)) / $period;
          $pdi[$i] = array_sum(array_slice($dm_plus, 1, $period)) / $atr[$i] * 100;
          $mdi[$i] = array_sum(array_slice($dm_minus, 1, $period)) / $atr[$i] * 100;
        }
        else {
          // For the subsequent points, use the previous value and the current value
          $atr[$i] = ($atr[$i - 1] * ($period - 1) + $tr[$i]) / $period;
          $pdi[$i] = ($pdi[$i - 1] * ($period - 1) + ($dm_plus[$i] / $atr[$i]) * 100) / $period;
          $mdi[$i] = ($mdi[$i - 1] * ($period - 1) + ($dm_minus[$i] / $atr[$i]) * 100) / $period;
        }
        // Calculate the directional index
        if ($pdi[$i] + $mdi[$i] > 0) {
          // If both are positive, use the absolute difference over the sum
          $dx[$i] = abs($pdi[$i] - $mdi[$i]) / ($pdi[$i] + $mdi[$i]) * 100;
        }
        else {
          // If both are zero or negative, use zero
          $dx[$i] = 0;
        }
        // Calculate the average directional index
        if ($i >= ($period - 1) * 2) {
          // For the points after two periods, use the exponential moving average formula
          if ($i == ($period - 1) * 2) {
            // For the first point, use the simple average of the initial period
            $adx[$i] = array_sum(array_slice($dx, $period - 1, $period)) / $period;
          }
          else {
            // For the subsequent points, use the previous value and the current value
            $adx[$i] = ($adx[$i - 1] * ($period - 1) + $dx[$i]) / $period;
          }
        }
      }
    }
    // Normalize the adx to a score between -100 and 100
    $max_adx = 100;
    $min_adx = 0;
    $score = ($adx[count($adx) - 1] - $min_adx) / ($max_adx - $min_adx) * 200 - 100;
    // Return the score
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo $score;
    return $score;
  }



  // A function to calculate the aroon indicator for the gld etf
function aroon($url, $period) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps, high and low from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $high = $data["chart"]["result"][0]["indicators"]["quote"][0]["high"];
    $low = $data["chart"]["result"][0]["indicators"]["quote"][0]["low"];
    // Initialize the arrays for aroon up and down
    $aroon_up = array();
    $aroon_down = array();
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // Calculate the aroon up and down
      if ($i >= $period - 1) {
        // For the points after the period, use the formula based on the highest high and lowest low
        $highest_high = max(array_slice($high, $i - ($period - 1), $period));
        $lowest_low = min(array_slice($low, $i - ($period - 1), $period));
        $aroon_up[$i] = (($period - (array_search($highest_high, array_slice($high, $i - ($period - 1), $period)) + 1)) / $period) * 100;
        $aroon_down[$i] = (($period - (array_search($lowest_low, array_slice($low, $i - ($period - 1), $period)) + 1)) / $period) * 100;
      }
    }
    // Normalize the aroon difference to a score between -100 and 100
    $max_aroon_diff = 200;
    $min_aroon_diff = -200;
    $score = (($aroon_up[count($aroon_up) - 1] - $aroon_down[count($aroon_down) - 1]) - $min_aroon_diff) / ($max_aroon_diff - $min_aroon_diff) * 200 - 100;
    // Return the score
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo $score;
    return $score;
  }

    $fast = 12;
    $slow = 26;
    $signal = 9;


  // A function to calculate the macd for the gld etf
function macd($url, $fast, $slow, $signal) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps and close from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"];
    // Initialize the arrays for exponential moving averages, macd line and signal line
    $ema_fast = array();
    $ema_slow = array();
    $macd_line = array();
    $signal_line = array();
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // Calculate the exponential moving averages
      if ($i >= $fast - 1) {
        // For the points after the fast period, use the exponential moving average formula
        if ($i == $fast - 1) {
          // For the first point, use the simple average of the initial period
          $ema_fast[$i] = array_sum(array_slice($close, 0, $fast)) / $fast;
        }
        else {
          // For the subsequent points, use the previous value and the current value
          $ema_fast[$i] = ($close[$i] - $ema_fast[$i - 1]) * (2 / ($fast + 1)) + $ema_fast[$i - 1];
        }
      }
      if ($i >= $slow - 1) {
        // For the points after the slow period, use the exponential moving average formula
        if ($i == $slow - 1) {
          // For the first point, use the simple average of the initial period
          $ema_slow[$i] = array_sum(array_slice($close, 0, $slow)) / $slow;
        }
        else {
          // For the subsequent points, use the previous value and the current value
          $ema_slow[$i] = ($close[$i] - $ema_slow[$i - 1]) * (2 / ($slow + 1)) + $ema_slow[$i - 1];
        }
      }
      // Calculate the macd line
      if ($i >= max($fast - 1, $slow - 1)) {
        // For the points after both periods, use the difference between fast and slow ema
        $macd_line[$i] = $ema_fast[$i] - $ema_slow[$i];
      }
      // Calculate the signal line
      if ($i >= max($fast - 1, $slow - 1) + ($signal - 1)) {
        // For the points after both periods plus signal period, use the exponential moving average formula
        if ($i == max($fast - 1, $slow - 1) + ($signal - 1)) {
          // For the first point, use the simple average of the initial period
          $signal_line[$i] = array_sum(array_slice($macd_line, max($fast - 1, $slow - 1), $signal)) / $signal;
        }
        else {
          // For the subsequent points, use the previous value and the current value
          $signal_line[$i] = ($macd_line[$i] - $signal_line[$i - 1]) * (2 / ($signal + 1)) + $signal_line[$i - 1];
        }
      }
    }
    // Normalize the macd difference to a score between -100 and 100
    $max_macd_diff = max($macd_line) - min($signal_line);
    $min_macd_diff = min($macd_line) - max($signal_line);
    if ($max_macd_diff > abs($min_macd_diff)) {
      // If positive difference is larger than negative difference, use it as maximum range
      $score = (($macd_line[count($macd_line) - 1] - $signal_line[count($signal_line) - 1]) / abs($max_macd_diff)) * 100;
    }
    else {
      // If negative difference is larger than positive difference, use it as maximum range
      $score = (($macd_line[count($macd_line) - 1] - $signal_line[count($signal_line) - 1]) / abs($min_macd_diff)) * (-100);
    }
    

    // Return the score
    echo '<br>';
     echo '<br>';
     echo '<br>';
    echo $score;
    return $score;
  }



  $period = 14;

  // A function to calculate the relative strength index (RSI) for the gld etf
    function rsi($url, $period) {
        // Get the json data from the url
        $json = file_get_contents($url);
        // Decode the json data into an associative array
        $data = json_decode($json, true);
        // Get the timestamps and close from the data
        $timestamps = $data["chart"]["result"][0]["timestamp"];
        $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"];
        // Initialize the arrays for price changes, average gains and losses, and relative strength
        $change = array();
        $avg_gain = array();
        $avg_loss = array();
        $rs = array();
        // Loop through the data points
        for ($i = 0; $i < count($timestamps); $i++) {
        // Calculate the price change
        if ($i > 0) {
            // For the subsequent points, use the difference between current and previous close
            $change[$i] = $close[$i] - $close[$i - 1];
        }
        // Calculate the average gain and loss
        if ($i >= $period) {
            // For the points after the period, use the formula based on previous and current gains and losses
            if ($i == $period) {
            // For the first point, use the simple average of the initial period
            $avg_gain[$i] = array_sum(array_filter(array_slice($change, 1, $period), function($x) {return $x > 0;})) / $period;
            $avg_loss[$i] = abs(array_sum(array_filter(array_slice($change, 1, $period), function($x) {return $x < 0;})) / $period);
            }
            else {
            // For the subsequent points, use the previous value and the current value
            if ($change[$i] > 0) {
                // If current change is positive, add it to the gain and use zero for loss
                $avg_gain[$i] = ($avg_gain[$i - 1] * ($period - 1) + $change[$i]) / $period;
                $avg_loss[$i] = ($avg_loss[$i - 1] * ($period - 1)) / $period;
            }
            else {
                // If current change is negative or zero, use zero for gain and add its absolute value to the loss
                $avg_gain[$i] = ($avg_gain[$i - 1] * ($period - 1)) / $period;
                $avg_loss[$i] = ($avg_loss[$i - 1] * ($period - 1) + abs($change[$i])) / $period;
            }
            }
        }
        // Calculate the relative strength
        if ($i >= $period) {
            // For the points after the period, use the ratio of average gain to average loss
            if ($avg_loss[$i] > 0) {
            // If average loss is positive, use it as denominator
            $rs[$i] = $avg_gain[$i] / $avg_loss[$i];
            }
            else {
            // If average loss is zero, use infinity as relative strength
            $rs[$i] = INF;
            }
        }
        }
        // Normalize the rsi to a score between -100 and 100
        $max_rsi = max($rs);
        if (is_infinite($max_rsi)) {
        // If maximum rsi is infinity, use a large number as approximation
        $max_rsi = pow(10,9);
        }
        
        // Calculate the rsi using inverse of logarithmic function
        $rsi = 100 - 100 / (1 + $rs[$i]);
        // Scale the rsi to a score between -100 and 100
        $score = ($rsi / $max_rsi) * 200 - 100;
        // Return the score
        echo '<br>';
     echo '<br>';
     echo '<br>';
        echo $score;
        return $score;
    }
  
    // A function to calculate the stochastic oscillator for the gld etf
function stoch($url, $k_period, $d_period) {
    // Get the json data from the url
    $json = file_get_contents($url);
    // Decode the json data into an associative array
    $data = json_decode($json, true);
    // Get the timestamps, high and low from the data
    $timestamps = $data["chart"]["result"][0]["timestamp"];
    $high = $data["chart"]["result"][0]["indicators"]["quote"][0]["high"];
    $low = $data["chart"]["result"][0]["indicators"]["quote"][0]["low"];
    $close = $data["chart"]["result"][0]["indicators"]["quote"][0]["close"]; // Added this line

    // Initialize the arrays for highest high, lowest low, %K line and %D line
    $highest_high = array();
    $lowest_low = array();
    $k_line = array();
    $d_line = array();
    // Loop through the data points
    for ($i = 0; $i < count($timestamps); $i++) {
      // Calculate the highest high and lowest low
      if ($i >= $k_period - 1) {
        // For the points after the k period, use the maximum and minimum of the previous k periods
        $highest_high[$i] = max(array_slice($high, $i - ($k_period - 1), $k_period));
        $lowest_low[$i] = min(array_slice($low, $i - ($k_period - 1), $k_period));
      }
      // Calculate the %K line
      if ($i >= $k_period - 1) {
        // For the points after the k period, use the formula based on current close, highest high and lowest low
        if ($highest_high[$i] != $lowest_low[$i]) {
          // If highest high is not equal to lowest low, use them as denominator and numerator
          $k_line[$i] = ($close[$i] - $lowest_low[$i]) / ($highest_high[$i] - $lowest_low[$i]) * 100;
        }
        else {
          // If highest high is equal to lowest low, use zero as %K value
          $k_line[$i] = 0;
        }
      }
      // Calculate the %D line
      if ($i >= ($k_period - 1) + ($d_period - 1)) {
        // For the points after both periods, use the simple moving average of the previous d periods of %K line
        $d_line[$i] = array_sum(array_slice($k_line, $i - ($d_period - 1), $d_period)) / $d_period;
      }
    }
    // Normalize the stochastic difference to a score between -100 and 100
    $max_stoch_diff = max($k_line) - min($d_line);
    $min_stoch_diff = min($k_line) - max($d_line);
    
   
     if ($max_stoch_diff > abs($min_stoch_diff)) {
      // If positive difference is larger than negative difference, use it as maximum range
      $score = (($k_line[count($k_line) - 1] - $d_line[count($d_line) - 1]) / abs($max_stoch_diff)) * 100;
    }
    else {
      // If negative difference is larger than positive difference, use it as maximum range
      $score = (($k_line[count($k_line) - 1] - $d_line[count($d_line) - 1]) / abs($min_stoch_diff)) * (-100);
    }
   
     // Return the score
     echo '<br>';
     echo '<br>';
     echo '<br>';

     echo $score;
    return $score;
  }
  
    $k_period = 14;
    $d_period = 3;


// A function to create an overall score based on the indicators and their weights
function overall_score($url, $weights) {
    // Define the parameters for each indicator
    $obv_period = 14;
    $adx_period = 14;
    $aroon_period = 14;
    $macd_fast = 12;
    $macd_slow = 26;
    $macd_signal = 9;
    $rsi_period = 14;
    $stoch_k_period = 14;
    $stoch_d_period = 3;
    
   
     // Calculate the scores for each indicator using the previous functions
    $obv_score = obv($url);
    $adx_score = adx($url, $adx_period);
    $aroon_score = aroon($url, $aroon_period);
    $macd_score = macd($url, $macd_fast, $macd_slow, $macd_signal);
    $rsi_score = rsi($url, $rsi_period);
    $stoch_score = stoch($url, $stoch_k_period, $stoch_d_period);
     
     // Create an array of the scores and their corresponding weights
    $scores = array(
      "obv" => array("score" => $obv_score, "weight" => $weights["obv"]),
      "adx" => array("score" => $adx_score, "weight" => $weights["adx"]),
      "aroon" => array("score" => $aroon_score, "weight" => $weights["aroon"]),
      "macd" => array("score" => $macd_score, "weight" => $weights["macd"]),
      "rsi" => array("score" => $rsi_score, "weight" => $weights["rsi"]),
      "stoch" => array("score" => $stoch_score, "weight" => $weights["stoch"])
    );
    
   
  
     // Calculate the weighted average of the scores
    $total_score = 0;
    $total_weight = 0;
    foreach ($scores as $indicator) {
      // Multiply the score by the weight and add it to the total score
      $total_score += ($indicator["score"] * $indicator["weight"]);
      // Add the weight to the total weight
      $total_weight += ($indicator["weight"]);
    }
    // Divide the total score by the total weight to get the average score
    if ($total_weight > 0) {
      // If total weight is positive, use it as denominator
      $average_score = ($total_score / $total_weight);
    }
    else {
      // If total weight is zero or negative, use zero as average score
      $average_score = 0;
    }
     
     
     // Return the average score
     return round($average_score,2); // Round to two decimal places
  }

  $weights = array(
    "obv" => 0.2,
    "adx" => 0.1,
    "aroon" => 0.1,
    "macd" => 0.2,
    "rsi" => 0.2,
    "stoch" => 0.2
);
$score = overall_score($url, $weights);

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
    <p><?php echo $score; ?></p>

    </div>
</body>
</html>

