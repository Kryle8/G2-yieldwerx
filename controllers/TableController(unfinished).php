<?php
require_once '../libs/Database.php';

class TableController {
    private $conn;

    private $filters = [];
    private $params = [];
    private $where_clause = '';
    private $join_table_clause = '';
    private $case_clause = '';
    public $tables = [];
    private $headers = [];
    private $all_columns = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getAllColumns() {
        return $this->all_columns;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function getParams() {
        return $this->params;
    }

    public function getWhereClause() {
        return $this->where_clause;
    }

    public function getJoinTableClause() {
        return $this->join_table_clause;
    }

    public function getCaseClause() {
        return $this->case_clause;
    }

    public function init() 
    {
        // Filters from selection_criteria.php
        $this->filters = [
            "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
            "l.Work_Center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
            "l.Part_Type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
            "l.Lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
            "w.Wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
            "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : []
        ];

        // Prepare SQL filters
        $sql_filters = [];
        foreach ($this->filters as $key => $values) {
            if (!empty($values)) {
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $sql_filters[] = "$key IN ($placeholders)";
                $this->params = array_merge($this->params, $values);
            }
        }

        // Create the WHERE clause if filters exist
        if (!empty($sql_filters)) {
            $this->where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
        }

        // Get the corresponding table names
        $query = "SELECT DISTINCT tm.Table_Name 
                FROM LOT l
                JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                $this->where_clause";
        
        $stmt = sqlsrv_query($this->conn, $query, $this->params);
        if ($stmt === false) {
            die('Query failed: ' . print_r(sqlsrv_errors(), true));
        }
        $this->tables = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $this->tables[] = $row['Table_Name'];
        }
        sqlsrv_free_stmt($stmt);

        // Build join table clause
        $joins = [];
        for ($i = 0; $i < count($this->tables); $i++) {
            $joins[] = "LEFT JOIN " . $this->tables[$i] . " ON " . $this->tables[$i] . ".Wafer_Sequence = w.Wafer_Sequence";
        }

        if (!empty($joins)) {
            $this->join_table_clause = implode("\n", $joins);
        }

        // Define the query to get the mappings
        $query = "
        SELECT DISTINCT Program_Name, Table_Name, Column_Name
        FROM TEST_PARAM_MAP
        WHERE Program_Name IN (" . implode(',', array_fill(0, count($this->filters['l.Program_Name']), '?')) . ") 
        AND Column_Name IN (" . implode(',', array_fill(0, count($this->filters['tm.Column_Name']), '?')) . ")";

        $stmt = sqlsrv_query($this->conn, $query, array_merge($this->filters['l.Program_Name'], $this->filters['tm.Column_Name']));
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Initialize arrays
        $tableToProgram = [];
        $columnToTables = [];

        // Process the results
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $programName = $row['Program_Name'];
            $tableName = $row['Table_Name'];
            $columnName = $row['Column_Name'];

            // Populate program-to-tables mapping
            if ($programName && $tableName) {
                if (!isset($tableToProgram[$tableName])) {
                    $tableToProgram[$tableName] = [];
                }
                if (!in_array($programName, $tableToProgram[$tableName])) {
                    $tableToProgram[$tableName][] = $programName;
                }
            }

            // Populate column-to-tables mapping
            if ($columnName && $tableName) {
                if (!isset($columnToTables[$columnName])) {
                    $columnToTables[$columnName] = [];
                }
                if (!in_array($tableName, $columnToTables[$columnName])) {
                    $columnToTables[$columnName][] = $tableName;
                }
            }
        }

        // Generate SQL CASE statements
        $caseStatements = [];
        foreach ($columnToTables as $column => $tables) {
            $whenClauses = [];
            foreach ($tables as $tableName) {
                foreach ($tableToProgram[$tableName] as $programName) {
                    $whenClauses[] = "WHEN l.Program_Name = '$programName' AND tm.Column_Name = '$column' THEN $tableName.$column";
                }
            }
            $caseStatements[] = "
                CASE 
                    " . implode(' ', $whenClauses) . " 
                    ELSE NULL 
                END AS '$column'";
        }
        
        if (!empty($caseStatements)) {
            $this->case_clause = implode(",", $caseStatements);
        }
    }


    public function getCount() 
    {
        // Count total number of records with filters
        $query = "SELECT COUNT(*) AS total FROM LOT l
                    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->join_table_clause
                    $this->where_clause";  // Append WHERE clause if it exists

        $count_stmt = sqlsrv_query($this->conn, $query, $this->params);
        if ($count_stmt === false) {
            die('Query failed: ' . print_r(sqlsrv_errors(), true));
        }
        $total_rows = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC)['total'];
        sqlsrv_free_stmt($count_stmt); // Free the count statement here

        return $total_rows;
    }

    public function writeTableHeaders()
    {
        $query = "SELECT tm.Column_Name, tm.Test_Name, Test_Number FROM TEST_PARAM_MAP tm
                 ORDER BY Test_Number ASC";

        $stmt = sqlsrv_query($this->conn, $query, $this->params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Create an array to map Column_Name to Test_Name
        $column_to_test_name_map = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['Column_Name']) && !empty($row['Test_Name'])) {
                $column_to_test_name_map[$row['Column_Name']] = '[' . substr($row['Column_Name'], 1) . ']' . $row['Test_Name'];
            }
        }
        sqlsrv_free_stmt($stmt); // Free the statement here after fetching the mapping

        // static columns
        $columns = ['ID', 'Facility', 'Work Center', 'Device Name', 'Test Program', 'Lot ID', 'Lot Test Temperature', 'Wafer ID', 'Wafer Start Time', 'Wafer Finish Time', 'Unit Number', 'Head Number', 'Site Number', 'Hard Bin No', 'Soft Bin No', 'Tests Executed', 'Test Time (ms)', 'Die Type', 'Home Die', 'Alignment Die', 'Include In Yield', 'Include In Die Count', 'Reticle Number', 'Reticle Position Row', 'Reticle Position Column', 'Reticle Active Sites Count', 'Reticle Site Position Row', 'Reticle Site Position Column', 'Part Number', 'Part Name', 'Die ID', 'Die Name', 'SINF', 'User Defined Attribute 1', 'User Defined Attribute 2', 'User Defined Attribute 3', 'Die Start Time', 'Die End Time'];

        // sql static + dynamic columns
        $this->all_columns = array_merge($columns, $this->filters['tm.Column_Name']);
        
        // table headers
        $this->headers = array_map(function($column) use ($column_to_test_name_map) {
            return isset($column_to_test_name_map[$column]) ? $column_to_test_name_map[$column] : $column;
        }, $this->all_columns);

        foreach ($this->headers as $header) {
            echo "<th class='px-6 py-3 whitespace-nowrap'>$header</th>";
        }
    }

    public function writeTableData()
    {
        // Retrieve all records with filters
        $query = "SELECT l.Facility_ID AS Facility, 
                        l.Work_Center AS Work_Center, 
                        l.Part_Type AS Device_Name, 
                        l.Program_Name AS Test_Program, 
                        l.Lot_ID AS Lot_ID, 
                        w.Wafer_ID AS Wafer_ID, 
                        w.Wafer_Start_Time AS Wafer_Start_Time, 
                        w.Wafer_Finish_Time AS Wafer_Finish_Time, 
                        " . $this->tables[0] . ".Unit_Number AS Unit_Number, 
                        " . $this->tables[0] . ".Head_Number AS Head_Number, 
                        " . $this->tables[0] . ".Site_Number AS Site_Number, 
                        " . $this->tables[0] . ".HBin_Number AS Hard_Bin_No, 
                        " . $this->tables[0] . ".SBin_Number AS Soft_Bin_No, 
                        " . $this->tables[0] . ".Tests_Executed AS Tests_Executed, 
                        " . $this->tables[0] . ".Test_Time AS Test_Time, 
                        $this->case_clause 
                FROM LOT l
                JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                JOIN ProbingSequenceOrder p ON p.probing_sequence = w.probing_sequence
                $this->join_table_clause
                $this->where_clause
                ORDER BY l.Lot_ID, w.Wafer_ID";

        $stmt = sqlsrv_query($this->conn, $query, $this->params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $i = 1;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr class='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>";
            foreach ($this->all_columns as $column) {
                $value = isset($row[$column]) ? $row[$column] : '';
                if ($column === "ID") {
                    $value = $i;
                }
                if ($value instanceof DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                echo "<td class='px-6 py-3 whitespace-nowrap text-center'>$value</td>";
            }
            echo "</tr>";
            $i++;
        }
        sqlsrv_free_stmt($stmt);
    }


}
