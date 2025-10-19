<?php
class Cake {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "cake_shop";
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }
    }

    // Get all cakes
    public function getAllCakes() {
        $sql = "SELECT * FROM cakes";
        return $this->conn->query($sql);
    }

    // Get cake by ID
    public function getCakeById($id) {
        $sql = "SELECT * FROM cakes WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
