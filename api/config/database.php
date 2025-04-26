<?php

    class Database{
    
        // specify your own database credentials
        private $host = "";
        private $db_name = "";
        private $username = "";
        private $password = "";
        public $conn = null;

            
        public function __construct($hostarr){
            
            $this->host = $hostarr["dbconnx"]["HOST"];
            $this->db_name = $hostarr["dbconnx"]["DB_NAME"];
            $this->username = $hostarr["dbconnx"]["USERNAME"];
            $this->password = $hostarr["dbconnx"]["PASSWORD"];
           
        }
            
        // get the database connection
        public function getConnection()
        {

           try{
                $this->conn = new PDO("mysql:host=$this->host;dbname=$this->db_name",$this->username,$this->password);
                $this->conn->exec("set names utf8");
                // echo "Connected";

            }catch(PDOException $exception){
                echo "Connection error: " . $exception->getMessage();
            }
    
            return $this->conn;

        }


    }


    //$bdconnect = new Database();
    //$bdconnect->getConnection();





