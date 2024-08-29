<?php
    require_once('../libs/Database.php');

    class TableController {
        private $conn;

        private $filters = [];
        private $params = [];
        private $where_clause = '';
        private $join_table_clause = '';
        public $tables = [];
        private $headers = [];
        private $all_columns = [];
        private $static_columns = [];
        private $dynamic_columns = [];


        public function __construct() {
            $database = new Database();
            $this->conn = $database->connect();
        }

        public function init() 
        {
            // Ensure parameter_x and parameter_y are always arrays
            $parameterX = isset($_GET['parameter_x']) && is_array($_GET['parameter_x']) ? $_GET['parameter_x'] : [];
            $parameterY = isset($_GET['parameter_y']) && is_array($_GET['parameter_y']) ? $_GET['parameter_y'] : [];

            // Filters from selection_criteria.php
            $this->filters = [
                "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
                "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
                "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
                "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
                "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
                "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
                "tm.Column_Name" => array_merge($parameterX, $parameterY)
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

            $groupXY = ['x' => isset($_GET["group-x"]) ? $_GET["group-x"][0] : null,
                        'y' => isset($_GET["group-y"]) ? $_GET["group-y"][0] : null];

            $filterXY = ['x' => isset($_GET['filter-x']) ? $_GET['filter-x'] : [],
                         'y' => isset($_GET['filter-y']) ? $_GET['filter-y'] : []];

            foreach ($groupXY as $key => $value) {
                if (!empty($value) && !empty($filterXY[$key])) {
                    if ($value === "Program_Name") {
                        $value = "l.Program_Name";
                    } else if ($value === "Probing_Sequence") {
                        $value = "p.abbrev";
                    }

                    $placeholders = implode(',', array_fill(0, count($filterXY[$key]), '?'));
                    $sql_filters[] = "$value IN ($placeholders)";
                    $this->params = array_merge($this->params, $filterXY[$key]);
                }
            }

            // Create the WHERE clause if filters exist
            if (!empty($sql_filters)) {
                $this->where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
            }

            // get the corresponding table names
            $query = "SELECT distinct tm.Table_Name FROM LOT l
                      JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                      JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                      JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                      $this->where_clause";
            
            $stmt = sqlsrv_query($this->conn, $query, $this->params);
            if ($stmt === false) { die('Query failed: ' . print_r(sqlsrv_errors(), true)); }
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $this->tables[] = $row['Table_Name']; }
            sqlsrv_free_stmt($stmt); // Free the count statement here   
            
            // Define the query to get the mappings
            $query = "
            SELECT DISTINCT Program_Name, Table_Name, Column_Name
            FROM TEST_PARAM_MAP
            WHERE Program_Name IN (" . implode(',', array_fill(0, count($this->filters['l.program_name']), '?')) . ") AND Column_Name IN (" . implode(',', array_fill(0, count($this->filters['tm.Column_Name']), '?')) . ");
            ";

            $stmt = sqlsrv_query($this->conn, $query, array_merge($this->filters['l.program_name'], $this->filters['tm.Column_Name']));
            if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

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
                    if (!isset($programToTables[$tableName])) {
                        $tableToProgram[$tableName] = [];
                    }
                    if (!in_array($tableName, $tableToProgram[$tableName])) {
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

            $joins = [];
            $programTracking = []; // To track the first table in each program

            foreach ($this->tables as $currentTable) {
                $programName = $tableToProgram[$currentTable][0]; // Get the program name for the current table

                if (!isset($programTracking[$programName])) {
                    // This is the first table in the program
                    $joins[] = "LEFT JOIN " . $currentTable . " ON " . $currentTable . ".Wafer_Sequence = w.Wafer_Sequence AND l.Program_Name = '{$programName}'";
                    $programTracking[$programName] = $currentTable; // Track this table as the first in the program
                } else {
                    // Subsequent table in the same program
                    $joins[] = "LEFT JOIN " . $currentTable . " ON " . $currentTable . ".Die_Sequence = " . $programTracking[$programName] . ".Die_Sequence AND l.Program_Name = '{$programName}'";
                }
            }

            if (!empty($joins)) {
                $this->join_table_clause = implode("\n", $joins);
            }

            // Generate static and dynamic columns for Unit_Number to Test Parameters
            $columns = ['Unit_Number','X','Y','Head_Number','Site_Number','HBin_Number','SBin_Number','Tests_Executed','Test_Time','DieType_Sequence','IsHomeDie','IsAlignmentDie','IsIncludeInYield','IsIncludeInDieCount','ReticleNumber','ReticlePositionRow','ReticlePositionColumn','ReticleActiveSitesCount','ReticleSitePositionRow','ReticleSitePositionColumn','PartNumber','PartName','DieID','DieName','SINF','DieStartTime','DieEndTime'];

            $this->static_columns = [];
            $firstTablePerProgram = []; // To track the first table for each program

            foreach ($this->tables as $table) {
                $programName = $tableToProgram[$table][0]; // Get the program name for the table

                // Track the first table for each program
                if (!isset($firstTablePerProgram[$programName])) {
                    $firstTablePerProgram[$programName] = $table;
                }
            }

            foreach ($columns as $column) {
                $coalesceParts = [];

                foreach ($firstTablePerProgram as $programName => $table) {
                    $coalesceParts[] = "{$table}.{$column}";
                }

                // Create COALESCE expression using only the first table in each program
                $coalesceExpression = count($coalesceParts) === 1 ? $coalesceParts[0] : "COALESCE(" . implode(", ", $coalesceParts) . ")";
                $columnName = str_replace('_', ' ', $column); // Convert column name to display name
                $this->static_columns[] = "{$coalesceExpression} AS '{$columnName}'";
            }
            
            $this->dynamic_columns = [];
            foreach ($columnToTables as $column => $tables) {
                // Generate COALESCE statement for each column with all specified tables
                $coalesceParts = [];
                foreach ($tables as $table) {
                    $coalesceParts[] = "{$table}.{$column}";
                }
                $coalesceExpression = count($coalesceParts) === 1 ? $coalesceParts[0] : "COALESCE(" . implode(", ", $coalesceParts) . ")";
                $columnName = str_replace('_', ' ', $column); // Convert column name to display name
                $this->dynamic_columns[] = "{$coalesceExpression} AS '{$columnName}'";
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
            $columns = ['ID', 'Facility', 'Work Center', 'Device Name', 'Test Program', 'Lot ID', 'Lot Test Temprature', 'Wafer ID', 'Wafer Start Time', 'Wafer Finish Time', 'Unit Number', 'Head Number', 'Site Number', 'HBin Number', 'SBin Number', 'Tests Executed', 'Test Time','DieType Sequence','IsHomeDie','IsAlignmentDie','IsIncludeInYield','IsIncludeInDieCount','ReticleNumber','ReticlePositionRow','ReticlePositionColumn','ReticleActiveSitesCount','ReticleSitePositionRow','ReticleSitePositionColumn','PartNumber','PartName','DieID','DieName','SINF','DieStartTime','DieEndTime'];

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
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', ". implode(", ", array_merge($this->static_columns, $this->dynamic_columns)) ."
                    FROM LOT l
                    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->join_table_clause
                    $this->where_clause
                    ORDER BY l.Lot_ID, w.Wafer_ID";

                    // echo $query;

            $i = 1;
            $stmt = sqlsrv_query($this->conn, $query, $this->params); // Re-execute query to fetch data for display
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr class='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>";
                foreach ($this->all_columns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    if ($column === "ID") {
                        $value = $i;
                    }
                    if ($value instanceof DateTime) {
                        $value = $value->format('Y-m-d H:i:s'); // Adjust format as needed
                    }
                    echo "<td class='px-6 py-3 whitespace-nowrap text-center'>$value</td>";
                }
                echo "</tr>";
                $i++;
            }
            sqlsrv_free_stmt($stmt); // Free the statement here after displaying data
        }

        public function exportCSV()
        {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=wafer_data.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, $this->headers);
            
            // Dynamically construct the column part of the SQL query
            $column_list = !empty($this->filters['tm.Column_Name']) ? implode(', ',  $this->filters['tm.Column_Name']) : '*';

            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', Unit_Number 'Unit Number', X , Y, Head_Number 'Head Number', Site_Number 'Site Number', HBin_Number 'Hard Bin No', SBin_Number 'Soft Bin No', Tests_Executed 'Tests Executed', Test_Time 'Test Time (ms)', DieType_Sequence 'Die Type', IsHomeDie 'Home Die', IsAlignmentDie 'Alignment Die', IsIncludeInYield 'Include In Yield', IsIncludeInDieCount 'Include In Die Count', ReticleNumber 'Reticle Number', ReticlePositionRow 'Reticle Position Row', ReticlePositionColumn 'Reticle Position Column', ReticleActiveSitesCount 'Reticle Active Sites Count', ReticleSitePositionRow 'Reticle Site Position Row', ReticleSitePositionColumn 'Reticle Site Position Column', PartNumber 'Part Number', PartName 'Part Name', DieID 'Die ID', DieName 'Die Name', SINF 'SINF', UserDefinedAttribute1 'User Defined Attribute 1', UserDefinedAttribute2 'User Defined Attribute 2', UserDefinedAttribute3 'User Defined Attribute 3', DieStartTime 'Die Start Time', DieEndTime 'Die End Time', $column_list FROM LOT l
                    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->join_table_clause
                    $this->where_clause
                    ORDER BY l.Lot_ID, w.Wafer_ID";

            // Re-execute query to fetch data for export
            $stmt = sqlsrv_query($this->conn, $query, $this->params);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $csv_row = [];
                foreach ($this->all_columns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    if ($value instanceof DateTime) {
                        $csv_row[] = $value->format('Y-m-d H:i:s');
                    } else {
                        $csv_row[] = (string)$value;
                    }
                }
                fputcsv($output, $csv_row);
            }

            fclose($output);
            sqlsrv_free_stmt($stmt);
        }
    }