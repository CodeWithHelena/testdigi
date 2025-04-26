<?php

class Customer extends User
{

    // constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
        $this->table_name = "customers";
    }


    
}
