<!DOCTYPE html>
<html>
<head>
  <title>Contact</title>
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
  <br><br><br><br>
  <button onclick="sendEmail()">Send Email</button>

  <script>
    function sendEmail() {
      window.open('mailto:freddrake14@gmail.com');
    }
  </script>
</body>
</html>
