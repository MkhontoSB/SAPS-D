<? php

    if ($_SERVER ["REQUEST_METHOD"] !== "POST") {
        die("Invalid Request");  
    }
        if(!isset($_FILES["pdf"])) {
            die("No File uploaded");
        }
            $_FILE = $_FILES["pdf"];
            
    
?>