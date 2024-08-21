<?php
require_once '../libs/Database.php';

class LineChartController {
    private $conn;
    private $filters = [];
    private $params = [];
    private $where_clause = '';
    private $join_clause = '';
    private $orderByClause = '';
    private $xColumn = null;
    private $yColumn = null;


    public function getXColumn() {
        return $this->xColumn;
    }

    public function getYColumn() {
        return $this->yColumn;
    }

    public function __construct($xIndex, $yIndex, $columns) {
        $database = new Database();
        $this->conn = $database->connect();

        $this->xColumn = isset($columns[$xIndex]) ? $columns[$xIndex] : null;
        $this->yColumn = isset($columns[$yIndex]) ? $columns[$yIndex] : null;

        $this->filters = [
            "l.Facility_ID" => $_GET['facility'] ?? [],
            "l.work_center" => $_GET['work_center'] ?? [],
            "l.part_type" => $_GET['device_name'] ?? [],
            "l.Program_Name" => $_GET['test_program'] ?? [],
            "l.lot_ID" => $_GET['lot'] ?? [],
            "w.wafer_ID" => $_GET['wafer'] ?? [],
            "tm.Column_Name" => $_GET['parameter'] ?? [],
            "p.abbrev" => $_GET['abbrev'] ?? []
        ];

        $this->buildWhereClause();
        $this->buildOrderByClause();
    }

    private function buildWhereClause() {
        $sql_filters = [];
        foreach ($this->filters as $key => $values) {
            if (!empty($values)) {
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $sql_filters[] = "$key IN ($placeholders)";
                $this->params = array_merge($this->params, $values);
            }
        }

        if (!empty($sql_filters)) {
            $this->where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
        }
    }

    private function buildOrderByClause() {
        $orderDirectionX = isset($_GET['order-x']) && $_GET['order-x'] == 1 ? 'DESC' : 'ASC';
        $orderDirectionY = isset($_GET['order-y']) && $_GET['order-y'] == 1 ? 'DESC' : 'ASC';

        if ($this->xColumn && $this->yColumn) {
            $this->orderByClause = "ORDER BY {$this->xColumn} {$orderDirectionX}, {$this->yColumn} {$orderDirectionY}";
        } elseif ($this->xColumn) {
            $this->orderByClause = "ORDER BY {$this->xColumn} {$orderDirectionX}";
        } elseif ($this->yColumn) {
            $this->orderByClause = "ORDER BY {$this->yColumn} {$orderDirectionY}";
        }
    }

    public function fetchData($parameter) {
        $tsql = "SELECT TOP 10 * FROM DEVICE_1_CP1_V1_0_001"; // Simplified query
        $stmt = sqlsrv_query($this->conn, $tsql);
    
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    
        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
    
        sqlsrv_free_stmt($stmt);
    
        // Debugging output
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    
        return $results;
    }
    

}
?>
