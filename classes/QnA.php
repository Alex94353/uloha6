<?php
namespace otazkyodpovede;

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/db/config.php');
use PDO;

class QnA {
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $config = DATABASE;

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        try {
            $this->conn = new PDO(
                'mysql:host=' . $config['HOST'] .
                ';dbname=' . $config['DBNAME'] .
                ';port=' . $config['PORT'],
                $config['USER_NAME'],
                $config['PASSWORD'],
                $options
            );
        } catch (PDOException $e) {
            die("Chyba pripojenia: " . $e->getMessage());
        }
    }
    
    public function insertQnA() {
        try {
            // Načítanie JSON súboru
            $data = json_decode(file_get_contents(__ROOT__ . '/data/datas.json'), true);
            $otazky = $data["otazky"];
            $odpovede = $data["odpovede"];
    
            // Vloženie otázok a odpovedí v rámci transakcie
            $this->conn->beginTransaction();
    
            $sqlInsert = "INSERT INTO qna (otazka, odpoved) VALUES (:otazka, :odpoved)";
            $sqlCheck = "SELECT COUNT(*) FROM qna WHERE otazka = :otazka AND odpoved = :odpoved";
    
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtCheck = $this->conn->prepare($sqlCheck);
    
            for ($i = 0; $i < count($otazky); $i++) {
                // overenie dublikatov
                $stmtCheck->execute([
                    ':otazka' => $otazky[$i],
                    ':odpoved' => $odpovede[$i]
                ]);
    
                if ($stmtCheck->fetchColumn() == 0) {
                    // insert ako to prvykrat
                    $stmtInsert->execute([
                        ':otazka' => $otazky[$i],
                        ':odpoved' => $odpovede[$i]
                    ]);
                }
            }
    
            $this->conn->commit();
            echo "Dáta boli vložené (bez duplicít)";
        } catch (Exception $e) {
            echo "Chyba pri vkladaní: " . $e->getMessage();
            $this->conn->rollback();
        } finally {
            $this->conn = null;
        }
    }
    
    

    public function getAllQnA() {
        try {
            $sql = "SELECT otazka, odpoved FROM qna";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Chyba pri načítaní dát: " . $e->getMessage();
            return [];
        }
    }
    
    
}    
