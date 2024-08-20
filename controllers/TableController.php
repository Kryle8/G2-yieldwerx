<?php
require_once '../libs/Database.php';

class TableController {
    private $conn;
    private $filters = [];
    private $params = [];
    private $where_clause = '';
    private $join_clause = '';
    public $tables = [];
    private $headers = [];
    private $all_columns = [];
    private $total_rows = 0;
    private $orderByClause = '';
    private $columnsGroup = [];
    private $testProgram;

    public function __construct($testProgram) {
        $this->testProgram = $testProgram;
        $database = new Database();
        $this->conn = $database->connect();

        $this->columnsGroup = [
            'l.Facility_ID', 'd1.Head_Number', 'd1.HBin_Number', 'l.Lot_ID', 'l.Part_Type', 'p.abbrev', 
            'l.Program_Name', 'd1.SBin_Number', 'd1.Site_Number', 'l.Test_Temprature', 'd1.Test_Time', 
            'd1.Tests_Executed', 'd1.Unit_Number', 'w.Wafer_Finish_Time', 'w.Wafer_ID', 
            'w.Wafer_Start_Time', 'l.Work_Center', 'd1.X', 'd1.Y', 'l.Program_Name'
        ];
    }

    public function init() {
        $this->filters = [
            "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
            "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
            "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
            "l.Program_Name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
            "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
            "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
            "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : [], // Updated key
            "p.abbrev" => isset($_GET['abbrev']) ? $_GET['abbrev'] : [],
        ];

        $this->buildWhereClause();
        $this->retrieveTables();
        $this->buildJoinClause();
        $this->buildOrderByClause();
        $this->retrieveData();
    }

    public function getTotalRows() {
        return $this->total_rows;
    }
    
    public function getHeaders() {
        if (empty($this->headers)) {
            $data = $this->fetchData(0, 1);
            if (!empty($data)) {
                $this->headers = array_keys($data[0]);
            }
        }
        return $this->headers;
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

    private function retrieveTables() {
        $programNamePlaceholders = implode(',', array_fill(0, count($this->filters['l.Program_Name']), '?'));
        $query = "SELECT DISTINCT table_name 
                  FROM TEST_PARAM_MAP 
                  WHERE program_name IN ($programNamePlaceholders)";
        
        // echo "Table retrieval query: " . $query . "<br>";
        // echo "Filters for table retrieval: " . print_r($this->filters['l.Program_Name'], true) . "<br>";
    
        $stmt = sqlsrv_query($this->conn, $query, $this->filters['l.Program_Name']);
        if ($stmt === false) {
            die('Query failed: ' . print_r(sqlsrv_errors(), true));
        }
    
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // echo "Retrieved table name: " . $row['table_name'] . "<br>";
            $this->tables[] = $row['table_name'];
        }
        sqlsrv_free_stmt($stmt);
    }

    private function buildJoinClause() {
        if (count($this->tables) < 2) {
            die('Not enough tables retrieved to form a valid query.');
        }

        $join_clauses = [];
        $previousAlias = 'w';
        $aliasIndex = 1;

        // Join TEST_PARAM_MAP first
        $join_clauses[] = "JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence";

        // Then join the dynamically retrieved tables
        foreach ($this->tables as $table) {
            $alias = "d$aliasIndex";

            if ($previousAlias === 'w') {
                $join_clauses[] = "JOIN $table $alias ON $previousAlias.Wafer_Sequence = $alias.Wafer_Sequence";
            } else {
                $join_clauses[] = "JOIN $table $alias ON $previousAlias.Die_Sequence = $alias.Die_Sequence";
            }

            $previousAlias = $alias;
            $aliasIndex++;
        }

        $this->join_clause = implode(' ', $join_clauses);
    }

    private function buildOrderByClause() {
        if (empty($this->orderByClause)) {
            $this->orderByClause = "l.Facility_ID ASC";
        }
    }

    public function fetchData($offset = 0, $limit = 100) {
        $dynamicColumns = $this->filters['tm.Column_Name'];
        $columnsToSelect = array_map(function($col) { return "CAST($col AS VARCHAR(255)) AS $col"; }, $dynamicColumns);
        
        $defaultColumns = [
            "l.Facility_ID", 
            "l.Work_Center", 
            "l.Part_Type", 
            "l.Program_Name", 
            "l.Test_Temprature", 
            "l.Lot_ID", 
            "w.Wafer_ID", 
            "CONVERT(VARCHAR, w.Wafer_Start_Time, 120) AS Wafer_Start_Time", 
            "CONVERT(VARCHAR, w.Wafer_Finish_Time, 120) AS Wafer_Finish_Time", 
            "d1.Unit_Number", 
            "d1.X", 
            "d1.Y", 
            "d1.Head_Number", 
            "d1.Site_Number", 
            "d1.HBin_Number", 
            "d1.SBin_Number"
        ];
        
        $allColumns = array_merge($defaultColumns, $columnsToSelect);
        $columnsClause = implode(', ', $allColumns);
    
        $tsql = "SELECT $columnsClause
                 FROM WAFER w 
                 JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
                 {$this->join_clause}
                 {$this->where_clause}
                 ORDER BY {$this->orderByClause}
                 OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        
        $params = array_merge($this->params, [$offset, $limit]);
    
        $stmt = sqlsrv_query($this->conn, $tsql, $params);
        if ($stmt === false) {
            die('Query failed: ' . print_r(sqlsrv_errors(), true));
        }
    
        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
    
        sqlsrv_free_stmt($stmt);
        return $results;
    }

    public function retrieveData() {
        // echo "Tables retrieved: " . implode(', ', $this->tables) . "<br>";
    
        if (count($this->tables) < 2) {
            die('Not enough tables retrieved to form a valid query.');
        }
    
        $facilityPlaceholders = implode(',', array_fill(0, count($this->filters['l.Facility_ID']), '?'));
        $workCenterPlaceholders = implode(',', array_fill(0, count($this->filters['l.work_center']), '?'));
        $partTypePlaceholders = implode(',', array_fill(0, count($this->filters['l.part_type']), '?'));
        $programNamePlaceholders = implode(',', array_fill(0, count($this->filters['l.Program_Name']), '?'));
        $lotIDPlaceholders = implode(',', array_fill(0, count($this->filters['l.lot_ID']), '?'));
        $waferIDPlaceholders = implode(',', array_fill(0, count($this->filters['w.wafer_ID']), '?'));
        $testParamMapPlaceholders = implode(',', array_fill(0, count($this->filters['tm.Column_Name']), '?'));
    
        $params = array_merge(
            $this->filters['l.Facility_ID'],
            $this->filters['l.work_center'],
            $this->filters['l.part_type'],
            $this->filters['l.Program_Name'],
            $this->filters['l.lot_ID'],
            $this->filters['w.wafer_ID'],
            $this->filters['tm.Column_Name']
        );
    
        $count_sql = "SELECT COUNT(w.Wafer_ID)
                      FROM WAFER AS w
                      JOIN LOT AS l ON l.Lot_Sequence = w.Lot_Sequence
                      JOIN {$this->tables[0]} AS d1 ON w.Wafer_Sequence = d1.Wafer_Sequence
                      JOIN {$this->tables[1]} AS d2 ON d1.Die_Sequence = d2.Die_Sequence
                      JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                      WHERE l.Facility_ID IN ($facilityPlaceholders)
                      AND l.work_center IN ($workCenterPlaceholders)
                      AND l.part_type IN ($partTypePlaceholders)
                      AND l.Program_Name IN ($programNamePlaceholders)
                      AND l.lot_ID IN ($lotIDPlaceholders)
                      AND w.wafer_ID IN ($waferIDPlaceholders)
                      AND tm.Column_Name IN ($testParamMapPlaceholders)";
        
        // echo "Count SQL: " . $count_sql . "<br>";
        // echo "Parameters: " . print_r($params, true) . "<br>";
    
        $stmt = sqlsrv_query($this->conn, $count_sql, $params);
    
        if ($stmt === false) {
            die('Count query failed: ' . print_r(sqlsrv_errors(), true));
        }
    
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        $this->total_rows = $result[0];
        
        // echo "<br>Total Rows: " . $this->total_rows . "<br>";
    }
    
}
?>
