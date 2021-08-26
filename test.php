<?php
/**
 * test url is
 * http://localhost/test.php?pagination=0 
 * 
 * @param pagination
 *  */ 

class ScrapRunner {
  private $conn;
  private $db;
  private $servername;
  private $username;
  private $password;

  public function __construct($servername, $db, $username, $password) {
    $this->servername = $servername;
    $this->db = $db;
    $this->username = $username;
    $this->password = $password;

    $this->init_db();
  }

  private function get_db_connection() {
    return $this->conn;
  }

  private function init_db() {
     // Create connection
    // Check connection
    // $conn = $this->connect_db();
    $conn = new mysqli($this->servername, $this->username, $this->password);

    if ($conn->connect_error) {
      die("Connection failed: " . $this->conn->connect_error);
      exit;
    }
    // echo "Connected successfully";
    #########################################################################
    // Create database 
    #########################################################################
    $sql = "CREATE DATABASE IF NOT EXISTS $this->db";
    if ($conn->query($sql) !== TRUE) {
      // echo "Database created successfully \n";
      echo "Error creating database: " . $conn->error;
    }

    #########################################################################
    // Use database
    #########################################################################

    $sql = "USE $this->db";
    if ($conn->query($sql) !== TRUE) {
      echo "Error use database: " . $conn->error;
    }

    #########################################################################
    // Create table
    #########################################################################
    $sql = "CREATE TABLE IF NOT EXISTS `products` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` mediumtext DEFAULT NULL,
      `url` mediumtext DEFAULT NULL,
      KEY `id` (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
      echo "Error creating table: " . $conn->error;
    }

    $this->conn = $conn;
  }

  public function add_product($name, $url) {
    $conn = $this->get_db_connection();
    $name = str_replace("'", "\\", $name);
    $url = str_replace("'", "\\", $url);
    $sql = "INSERT INTO `products` (`id`,`name`,`url`) VALUES (0, '$name', '$url');";

    if ($conn->query($sql) === TRUE) {
      // echo "product created";
      return 1;
    }
    // echo " $sql " ;
    // echo "<br/>" ;
    // echo ( $conn->error);
    return 0;
  }

  private function get_html ($init = 0, $limit = 96) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://www.bigbuy.eu/category/ajax_show_products',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'init=' . $init . '&limit=' . $limit . '&args=2488&sort=6&filters%5Bprice%5D%5B%5D=0&filters%5Bprice%5D%5B%5D=475&filters%5Border%5D=6&type=1&ajax=1',
      CURLOPT_HTTPHEADER => array(
        'authority: www.bigbuy.eu',
        'sec-ch-ua: "Chromium";v="92", " Not A;Brand";v="99", "Google Chrome";v="92"',
        'accept: */*',
        'x-requested-with: XMLHttpRequest',
        'sec-ch-ua-mobile: ?0',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
        'content-type: application/x-www-form-urlencoded; charset=UTF-8',
        'origin: https://www.bigbuy.eu',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://www.bigbuy.eu/fr/deguisements.html?page=1',
        'accept-language: en-US,en;q=0.9',
        'cookie: PHPSESSID=de0ebd164b5b245f0f2401e2acb27c44; bbidm=259183412; carrito_anonimo=0; cb-enabled=accepted; _ga=GA1.2.168398592.1629965092; _gid=GA1.2.280018371.1629965092; _gcl_au=1.1.1948134684.1629965092; _uetsid=5138ef20064411ec877dc1bf556e25fe; _uetvid=51392900064411ec82d50727865a44c6; messagesUtk=5dbe2d474b0c4a4a89e13fba8054eb93; __hstc=101017502.c2965601c3d4f4410502cb69998e5169.1629965127633.1629965127633.1629965127633.1; hubspotutk=c2965601c3d4f4410502cb69998e5169; __hssrc=1; __hssc=101017502.1.1629965127634'
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    $response = json_decode($response);
    
    $html_products = $response->data;
    
    return $html_products;
  }

  public function get_products($pagination_number = 0) {
    $html_products = $this->get_html($pagination_number * 96);

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html_products); // loads your HTML
    $xpath = new DOMXPath($doc);
    // returns a list of all links with rel=nofollow
    // $html_products = $xpath->query("//div[@class='productList-item']");
    $html_products = $xpath->query("//div[@class='productList-item']/article/figure/figcaption/div[1]/a");
    $html_images = $xpath->query("//div[@class='productList-item']/article/figure/a/img");
    $arr = array();

    for ($i=0; $i < count($html_products); $i++) {
      $product = array();
      $product["name"] = $html_products[$i]->nodeValue;
      $product["url"] = $html_images[$i]->getattribute("data-original");
      $arr[] = $product;
    }
    // var_dump(json_encode($arr));
    // exit;
    return $arr;
  }
}


$servername = "localhost";
$db = "test_db";
$username = "root";
$password = "";

$pagenation = 0 ;

try{
  $pagenation = intval($_GET["pagination"]);
} catch(Exception $e) {
  echo "$e";
  $pagenation = 0 ;
}


$runner =new ScrapRunner($servername,  $db, $username, $password);
$products = $runner->get_products($pagenation); # 1 means second pagination

foreach ($products as $product) {
  $runner->add_product($product['name'], $product['url']);
}


echo "Ok";