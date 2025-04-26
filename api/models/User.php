<?php

class User
{

    // database connection and table name
    // private $conn;
    protected $table_name = "users";

    // object properties
    public $user_id;
    public $firstname;
    public $lastname;
    public $email;
    public $password;
    public $phone;
    public $user_code;
    public $active;
    public $role_id;
    public $created_at;
    public $updated_at;
    public $conn = NULL;


    // constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }


    // read a single user
    public function getUser()
    {

            // select query if user ID is provided
            $query = "SELECT * FROM $this->table_name WHERE user_id=$this->user_id";

            // prepare query statement
            $stmt = $this->conn->prepare($query);

            try {

                $stmt->execute();

                return ["output"=>$stmt, "outputStatus"=>1000];
    
            } catch (Exception $e) {

                return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];

            }

    }

        // read a single user by email
        public function getUserByEmail()
        {
    
                // select query if user ID is provided
                $query = "SELECT * FROM $this->table_name WHERE email=:email";
    
                // prepare query statement
                $stmt = $this->conn->prepare($query);

                // bind values
                $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);

    
                try {
    
                    $stmt->execute();
    
                    return ["output"=>$stmt, "outputStatus"=>1000];
        
                } catch (Exception $e) {
    
                    return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
    
                }
    
        }

    // read users
    public function getAllUsers()
    {
        // select all query
        $query = "SELECT * FROM $this->table_name";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        try {
            // execute query
            $stmt->execute();

            return  ["output"=>$stmt, "outputStatus"=>1000];
            
        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }
    }


    // create user
    function createUser()
    {
        // Generate and set new user code
        $this->generateUserCode();
        $this->password = $this->hasherFunction($this->password);

        // query to insert record
        $query = "INSERT INTO $this->table_name (firstname, lastname, email, password, phone, user_code) VALUES (:firstname, :lastname, :email, :password, :phone, :user_code)";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":user_code", $this->user_code);

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


    // update the user
    function updateUser()
    {
        $this->setTimeNow();

        // update query
        $query = "UPDATE $this->table_name SET
                    firstname = :firstname,
                    lastname = :lastname,
                    password = :password,
                    updated_at = :updated_at
                WHERE
                    user_id = :user_id";

        // prepare query statement
        $update_stmt = $this->conn->prepare($query);

        // bind new values
        $update_stmt->bindParam(':firstname', $this->firstname);
        $update_stmt->bindParam(':lastname', $this->lastname);
        $update_stmt->bindParam(':password', $this->password);
        $update_stmt->bindParam(':updated_at', $this->updated_at);
        $update_stmt->bindParam(':user_id', $this->user_id);

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



        // update the user
        function updateUser2()
        {
            $this->setTimeNow();
    
            // update query
            $query = "UPDATE $this->table_name SET
                        firstname = :firstname,
                        lastname = :lastname,
                        password = :password,
                        active = :active,
                        role_id = :role_id,
                        updated_at = :updated_at
                        WHERE
                        user_id = :user_id";
    
            // prepare query statement
            $update_stmt = $this->conn->prepare($query);
    
            // bind new values
            $update_stmt->bindParam(':firstname', $this->firstname);
            $update_stmt->bindParam(':lastname', $this->lastname);
            $update_stmt->bindParam(':password', $this->password);
            $update_stmt->bindParam(':active', $this->active);
            $update_stmt->bindParam(':role_id', $this->role_id);
            $update_stmt->bindParam(':updated_at', $this->updated_at);
            $update_stmt->bindParam(':user_id', $this->user_id);
    
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
    

    // delete a user
    function deleteUser()
    {
        // delete query
        $query = "DELETE FROM $this->table_name WHERE user_id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // bind user_id of record to delete
        $stmt->bindParam(1, $this->user_id);

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
    function searchUser($searchstring, $col)
    {

        // select all query
        $query = "SELECT * FROM $this->table_name WHERE $col LIKE '%$searchstring%'";

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


    // Change user password
    function changePassword()
    {

        // Encrypt password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->setTimeNow();
        
        // Update query
        $query = "UPDATE $this->table_name SET
                    password = :password,
                    updated_at = :updated_at
                WHERE
                    user_id = :user_id";

        // Prepare query statement
        $change_stmt = $this->conn->prepare($query);

        // Bind new values
        $change_stmt->bindParam(':user_id', $this->user_id);
        $change_stmt->bindParam(':password', $this->password);
        $change_stmt->bindParam(':updated_at', $this->updated_at);

        try {
            $change_stmt->execute();

            if($change_stmt->rowCount() > 0){
                return ["output"=>true, "outputStatus"=>1000];
            } else {
                return ["output" => false, "outputStatus" => 1200];
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }

    }


        // Change user password
        function changePasswordByEmail()
        {
    
            // Encrypt password
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            $this->setTimeNow();
            
            // Update query
            $query = "UPDATE $this->table_name SET
                        password = :password,
                        updated_at = :updated_at
                        WHERE
                        email = :email";
    
            // Prepare query statement
            $change_stmt = $this->conn->prepare($query);
    
            // Bind new values
            $change_stmt->bindParam(':email', $this->email);
            $change_stmt->bindParam(':password', $this->password);
            $change_stmt->bindParam(':updated_at', $this->updated_at);
    
            try {
                $change_stmt->execute();
    
                if($change_stmt->rowCount() > 0){
                    return ["output"=>true, "outputStatus"=>1000];
                } else {
                    return ["output" => false, "outputStatus" => 1200];
                }
    
            } catch (Exception $e) {
                return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
            }
    
        }
    


    // Verify user with email and evcode
    function verifyEmailEvcode($evc)
    {
            
        // Select query
        $query = "SELECT user_id FROM $this->table_name WHERE email = :email AND user_code = :evcode";
            
        // Prepare query statement
        $select_stmt = $this->conn->prepare($query);

        // Bind new values
        $select_stmt->bindParam(':email', $this->email);
        $select_stmt->bindParam(':evcode', $evc);

        try {
            // execute query
            $select_stmt->execute();
            return  ["output"=>$select_stmt,  "outputStatus"=>1000];
            
        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }
            
    }
        
    


    // Email verification handler
    public function reGenEvCode($evcode)
    {

        // update query
        $query = "UPDATE $this->table_name SET
                    active = $evcode  WHERE
                    user_id = :user_id";

        // prepare query statement
        $active_stmt = $this->conn->prepare($query);

        // bind new values
        $active_stmt->bindParam(':user_id', $this->user_id);

        try {
            $active_stmt->execute();

            if($active_stmt->rowCount() > 0){
                return ["output"=>true, "outputStatus"=>1000];
            } else {
                return ["output" => false, "outputStatus" => 1200];
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }
    }


    // Handle login   
    public function userLogin()
    {

        $sql = "SELECT * FROM $this->table_name WHERE email=:email";

        // prepare query statement
        $login_stmt = $this->conn->prepare($sql);

        // bind new values
        $login_stmt->bindParam(':email', $this->email);

        try {

            $login_stmt->execute();
            return ["output"=>$login_stmt, "outputStatus"=>1000];

        } catch (Exception $e) {

            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];

        }
    }

    public function userLogout()
    {
        return $this->reGenerateUserCodeById();
       
    }


    // Generate ans set new user-code
    private function generateUserCode()
    {
        $this->user_code = substr(md5(time()), 0, 18) . $this->user_id . substr(md5(time()), 0, 18);

    }

    // Captures and set current system time
    private function setTimeNow()
    {
        $this->updated_at = date("Y:m:d H:i:sa");
    }

    // Updates the new user_code by user email in db
    public function reGenerateUserCode()
    {
        $this->generateUserCode();
        $this->setTimeNow();

        // update query
        $query = "UPDATE $this->table_name SET
              user_code = :user_code,
              updated_at = :updated_at
          WHERE
              email = :email";

        // prepare query statement
        $update_stmt = $this->conn->prepare($query);

        // bind new values
        $update_stmt->bindParam(':email', $this->email);
        $update_stmt->bindParam(':user_code', $this->user_code);
        $update_stmt->bindParam(':updated_at', $this->updated_at);

        try {
            $update_stmt->execute();

            if($update_stmt->rowCount() > 0){
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
        }


    }


        // Updates the new user_code by User ID in db
        public function reGenerateUserCodeById()
        {
            $this->generateUserCode();
            $this->setTimeNow();
    
            // update query
            $query = "UPDATE $this->table_name SET
                  user_code = :user_code,
                  updated_at = :updated_at
              WHERE
                  user_id = :user_id";
    
            // prepare query statement
            $update_stmt = $this->conn->prepare($query);
    
            // bind new values
            $update_stmt->bindParam(':user_id', $this->user_id);
            $update_stmt->bindParam(':user_code', $this->user_code);
            $update_stmt->bindParam(':updated_at', $this->updated_at);
    
            try {
                $update_stmt->execute();
    
                if($update_stmt->rowCount() > 0){
                    return true;
                } else {
                    return false;
                }
    
            } catch (Exception $e) {
                return ["output" => $e->getMessage(), "eror" => "Netork issue. Please try again.", "outputStatus" => 1400];
            }
    
    
        }
    

    // Verify password
    private function hasherFunction($str)
    {
        return password_hash($str, PASSWORD_DEFAULT);
    }


    // Verify password
    public function verifyPass($user_pass, $db_pass)
    {

        if (password_verify($user_pass, $db_pass)) {
            return true;
        } else {
            return false;
        }
    }


    // Auto generate new user password
    public function genPass()
    {
        
        $symbolsArray = ["!", "@", "#", "%", "*", "^", "(", ")", "[", "]", ";", ":"];
        return uniqid() . $symbolsArray[rand(0, 5)] . uniqid() . $symbolsArray[rand(0, 5)];

    }
    
}
