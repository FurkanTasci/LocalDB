<?php

use LocalDB\LocalDB;

class DatabaseTest extends PHPUnit_Extensions_Database_TestCase
{
    private $db;

    public function setup()
    {
        $this->db = $db;

        @chmod(__DIR__, 0775);
        @mkdir(__DIR__ . '/data', 0775, true); 
        
        $this->db = new LocalDB(__DIR__ . '/data');
    }

    public function testInsert()
    {
        $query = $this->db->insert('users', [
            'id' => 1,
            'name' => 'Furkan Tasci',
            'role' => 'Developer'
        ]);

        $this->assertTrue($query);
    }

    public function testDelete()
    {
        $query = $this->db->delete('users')->where([
            'id' => 2,
            'name' => 'name8'
        ], 'AND')->run();

        $this->assertNotEmpty($query);
    }
} 


?>