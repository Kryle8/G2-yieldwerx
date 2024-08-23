<?php
    require_once('../libs/Database.php');

    class SelectionController {
        private $conn;

        public function __construct() {
            $database = new Database();
            $this->conn = $database->connect();
        }

        public function getFacilities() 
        {
            $query = "SELECT distinct Facility_ID FROM LOT";

            $facilities = [];
            $stmt = sqlsrv_query($this->conn, $query);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $facilities[] = $row['Facility_ID'];
            }
            sqlsrv_free_stmt($stmt);

            return $facilities;
        }

        public function getProbingFilter() 
        {
            $filterQuery = "SELECT abbrev, probing_sequence FROM ProbingSequenceOrder";
            
            $abbrev = [];
            $filterStmt = sqlsrv_query($this->conn, $filterQuery);
            while ($row = sqlsrv_fetch_array($filterStmt, SQLSRV_FETCH_ASSOC)) {
                $abbrev[] = ['abbrev' => $row['abbrev'], 'probing_sequence' => $row['probing_sequence']];
            }
            sqlsrv_free_stmt($filterStmt);

            return $abbrev;
        }

        public function getOptions($type, $value)
        {
            $filters = json_decode($value, true);

            $where_clause = '';
            $sql_filters = [];
            foreach ($filters as $key => $value) {
                if ($value) {
                    $sql_filters[] = (($key === 'Program_Name') ? "lot.$key" : $key) . " IN ('" . implode("','", $value) . "') ";
                }
            }
            if (!empty($sql_filters)) {
                $where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
            }
            
            switch ($type) {
                case 'work_center':
                    $query = "SELECT distinct Work_Center FROM lot $where_clause";
                    break;
                case 'device_name':
                    $query = "SELECT distinct Part_Type FROM lot $where_clause";
                    break;
                case 'test_program':
                    $query = "SELECT distinct Program_Name FROM lot $where_clause";
                    break;
                case 'lot':
                    $query = "SELECT distinct Lot_ID FROM lot $where_clause";
                    break;
                case 'wafer':
                    $query = "SELECT distinct wafer.Wafer_ID FROM wafer
                    JOIN lot ON lot.Lot_Sequence = wafer.Lot_Sequence
                    $where_clause
                    GROUP BY wafer.Wafer_ID
                    ORDER BY wafer.wafer_ID ";
                    break;
                case 'parameter_x':
                    $query = "SELECT DISTINCT tm.Column_Name, tm.Test_Name, Test_Number
                              FROM TEST_PARAM_MAP tm 
                                JOIN lot ON lot.Lot_Sequence = tm.Lot_Sequence
                              JOIN wafer ON wafer.Lot_Sequence = tm.Lot_Sequence 
                              $where_clause
                              ORDER BY Test_Number ASC";
                    break;
                case 'parameter_y':
                    $query = "SELECT DISTINCT tm.Column_Name, tm.Test_Name, Test_Number
                            FROM TEST_PARAM_MAP tm 
                                JOIN lot ON lot.Lot_Sequence = tm.Lot_Sequence
                            JOIN wafer ON wafer.Lot_Sequence = tm.Lot_Sequence 
                            $where_clause
                            ORDER BY Test_Number ASC";
                    break;
                default:
                    $query = "";
            }
            
            $options = [];
            if ($query) {
                $stmt = sqlsrv_query($this->conn, $query);
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    if ($type == 'parameter_x') {
                        // $options[] = ['value' => $row['Column_Name'], 'display' => $row['Test_Name']];
                        $options[] = [
                            'value' => $row['Column_Name'],
                            'display' =>  $row['Column_Name'] . ' : ' . $row['Test_Name']
                        ];
                    }
                    else if ($type == 'parameter_y') {
                        // $options[] = ['value' => $row['Column_Name'], 'display' => $row['Test_Name']];
                        $options[] = [
                            'value' => $row['Column_Name'],
                            'display' =>  $row['Column_Name'] . ' : ' . $row['Test_Name']
                        ];
                    } else {
                        $options[] = array_values($row)[0];
                    }
                }
                sqlsrv_free_stmt($stmt);
            }
            
            return $options;
        }

        public function getFilters($columnName)
        {
            $query = "SELECT distinct $columnName FROM LOT l
                       join WAFER w on w.Lot_Sequence = l.Lot_Sequence";

            $options = [];
            $stmt = sqlsrv_query($this->conn, $query);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $options[] = array_values($row)[0];
            }
            sqlsrv_free_stmt($stmt);
            
            return $options;
        }

        public function getWaferHeaders()
        {
            $query = "SELECT TOP(1) * FROM WAFER";

            $headers = [];
            $stmt = sqlsrv_query($this->conn, $query);
            if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    if ($value !== null && $key !== 'Lot_Sequence' && $key !== 'Wafer_Sequence') {
                        $headers[] = $key;
                    }
                }
            }
            sqlsrv_free_stmt($stmt);

            sort($headers);
            return $headers;
        }
        
        public function getLotHeaders()
        {
            $query = "SELECT TOP(1)* FROM LOT";
            
            $headers = [];
            $stmt = sqlsrv_query($this->conn, $query);
            if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    if ($value !== null && $key !== 'Lot_Sequence') {
                        $headers[] = $key;
                    }
                }
            }
            sqlsrv_free_stmt($stmt);

            sort($headers);
            return $headers;
        }
    }