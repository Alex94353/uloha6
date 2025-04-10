<?php
namespace otazkyodpovede;

error_reporting(E_ALL); // zapnutie chybových hlásení
ini_set("display_errors", "On");

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/db/Database.php');

use db\Database;
use PDO;
use Exception;

class QnA extends Database {
    protected $connection;

    public function __construct() {
        $this->connect();
        $this->connection = $this->getConnection();
    }

    public function insertQnA() {
        try {
            $data = json_decode(file_get_contents(__ROOT__ . '/data/datas.json'), true);
            $otazky = $data["otazky"];
            $odpovede = $data["odpovede"];

            $this->connection->beginTransaction();

            $sqlInsert = "INSERT INTO qna (otazka, odpoved) VALUES (:otazka, :odpoved)";
            $sqlCheck = "SELECT COUNT(*) FROM qna WHERE otazka = :otazka AND odpoved = :odpoved";

            $stmtInsert = $this->connection->prepare($sqlInsert);
            $stmtCheck = $this->connection->prepare($sqlCheck);

            for ($i = 0; $i < count($otazky); $i++) {
                $stmtCheck->execute([
                    ':otazka' => $otazky[$i],
                    ':odpoved' => $odpovede[$i]
                ]);

                if ($stmtCheck->fetchColumn() == 0) {
                    $stmtInsert->execute([
                        ':otazka' => $otazky[$i],
                        ':odpoved' => $odpovede[$i]
                    ]);
                }
            }

            $this->connection->commit();
            echo "Dáta boli vložené (bez duplicít)";
        } catch (Exception $e) {
            $this->connection->rollback();
            echo "Chyba pri vkladaní: " . $e->getMessage();
        }
    }

    public function getAllQnA() {
        try {
            $sql = "SELECT otazka, odpoved FROM qna";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Chyba pri načítaní dát: " . $e->getMessage();
            return [];
        }
    }
}
