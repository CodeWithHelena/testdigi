<?php

class Token
{

    // database connection and table token
    // private $conn;
    protected $table_token = "tokens";

    // object properties
    public $id;
    public $token;
    public $used;
    public $created_by;
    public $created_at;
    public $used_by;
    public $date_used;

    public $conn = NULL;


    // constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }


    // read a single token
    public function getToken()
    {

            // select query if token ID is provided
            $query = "SELECT * FROM $this->table_token WHERE id=$this->id";

            // prepare query statement
            $stmt = $this->conn->prepare($query);

            try {

                $stmt->execute();

                return ["output"=>$stmt, "outputStatus"=>1000];
    
            } catch (Exception $e) {

                return ["output" => $e->getMessage(), "error" => "Netork issue. Please try again.", "outputStatus" => 1200];

            }

    }


    // read tokens
    public function getAllTokens()
    {
        // select all query
        $query = "SELECT * FROM $this->table_token";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        try {
            // execute query
            $stmt->execute();

            return  ["output"=>$stmt, "outputStatus"=>1000];
            
        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "error" => "Netork issue. Please try again.", "outputStatus" => 1200];
        }
    }


    // create token
    function createToken()
    {
        $this->token = substr(uniqid(rand()), 0, 16);
        // query to insert record
        $query = "INSERT INTO $this->table_token (token, created_by) VALUES (:token, :created_by)";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // bind values
        $stmt->bindParam(":token", $this->token);
        $stmt->bindParam(":created_by", $this->created_by);

        try {
            $stmt->execute();

            if($stmt->rowCount() > 0){
                return ["output"=>true, "outputStatus"=>1000];
            } else {
                return ["output" => false, "outputStatus" => 1100];
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1200];
        }
    }


        // create many token
        function createToken2($notoks)
        {
            $successCount = 0;
        
            for ($x = 0; $x < $notoks; $x++) { // changed to < 10 for exactly 10 runs
                $this->token = substr(uniqid(rand()), 0, 16);
        
                $query = "INSERT INTO $this->table_token (token, created_by) VALUES (:token, :created_by)";
        
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":token", $this->token);
                $stmt->bindParam(":created_by", $this->created_by);
        
                try {
                    $stmt->execute();
        
                    if ($stmt->rowCount() > 0) {
                        $successCount++;
                    }
                } catch (Exception $e) {
                    return [
                        "output" => $e->getMessage(),
                        "error" => "Network issue. Please try again.",
                        "outputStatus" => 1200
                    ];
                }
            }
        
            if ($successCount === 10) {
                return ["output" => true, "outputStatus" => 1000];
            } else {
                return ["output" => false, "outputStatus" => 1100, "successfulInserts" => $successCount];
            }
        }
            


    // update the token
    function updatetoken()
    {
        $this->updated_at = date("Y-m-d H:i:sa", time());

        // update query
        $query = "UPDATE $this->table_token SET
                    token = :token,
                    price = :price,
                    brand_token = :brand_token,
                    updated_at = :updated_at
                WHERE
                    id = :id";

        // prepare query statement
        $update_stmt = $this->conn->prepare($query);

        // bind new values
        $update_stmt->bindParam(':token', $this->token);
        $update_stmt->bindParam(':price', $this->price);
        $update_stmt->bindParam(':brand_token', $this->brand_token);
        $update_stmt->bindParam(':updated_at', $this->updated_at);
        $update_stmt->bindParam(':id', $this->id);

        try {
            $update_stmt->execute();

            if($update_stmt->rowCount() > 0){
                return ["output"=>true, "outputStatus"=>1000];
            } else {
                return ["output" => false, "outputStatus" => 1200];
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }

    }



    

    // delete a token
    function deletetoken()
    {
        // delete query
        $query = "DELETE FROM $this->table_token WHERE id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);

        try {
            $stmt->execute();

            if($stmt->rowCount() > 0){
                return ["output"=>true, "outputStatus"=>1000];
            } else {
                return ["output" => false, "outputStatus" => 1200];
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }
    }


    // search for a particular in a given column
    function searchtoken($searchstring, $col)
    {

        // select all query
        $query = "SELECT * FROM $this->table_token WHERE $col LIKE '%$searchstring%'";

        // prepare query statement
        $search_stmt = $this->conn->prepare($query);

        try {
            // execute query
            $search_stmt->execute();

            return  ["output"=>$search_stmt,  "outputStatus"=>1000];
            
        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }
    }


    
}
