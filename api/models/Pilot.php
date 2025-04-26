<?php

class Pilot extends User
{
    private $user_table = "users";
    
    // constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
        $this->table_name = "pilots";
    }


    
}
