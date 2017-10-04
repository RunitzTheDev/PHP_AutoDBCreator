<?php
//Server IP Address//
$SERVER_IP = "12.345.67.890";

//ID for Server Authentication//
$SERVER_ID = "exampleID";

//Password for Server Authentication//
$SERVER_PW = "examplePW";

//Database Name//
$DB_NAME = "exampleTable";

//DataTable Columns (Optional: Set Custom Column names for a sheet; example for sheet #-1 set)//
$table_columns = [
    -1 => array("column_1_name", 'column_2_name', 
    'column_3_name', "column_4_name")
];

//Database Table Names (Optional: Set Custom Table name for a sheet; example for sheet #-1 set)//
$DT_NAMES = [
    -1 => "example_table"
];



// DO NOT MODIFY BELOW //Server Connection Configuration
function server_configs() {
    global $SERVER_IP, $SERVER_ID, $SERVER_PW, $DB_NAME, $DT_NAME;
    
    $buf = array('dbhost' => $SERVER_IP, 'dbid' => $SERVER_ID, 'dbpw' => $SERVER_PW, 'dbname' => $DB_NAME, 'table_name' => $DT_NAME);
    return $buf;
}


function get_dt_name($sheet_num) {
    global $DT_NAMES;
    
    $result = NULL;
    if(isset($DT_NAMES[$sheet_num]))
    {
        $result = $DT_NAMES[$sheet_num];
    }
    return $result;
}

function set_dt_name($sheet_num, $table_name) {
    global $DT_NAMES;
    $DT_NAMES[$sheet_num] = $table_name;
}

//Returns all column names
function table_column_names($sheet_num) {
    
    global $table_columns;
    
    //Default return
    $result = array();
    
    //If column name table exists for given sheet number, return it
    if(isset($table_columns[$sheet_num]))
    {
        $result = $table_columns[$sheet_num];
    }

    return $result;
}

function set_table_columns($sheet_num, $column_names) {
    global $table_columns;
    $table_columns[$sheet_num] = $column_names;
//     $table_columns[] = 'id';
}

//Returns total column count
function table_column_count($sheet_num) {
    global $table_columns;
    
    //Default return
    $result = 0;
    
    //If Columns exist for this sheet number, return its count
    if(isset($table_columns[$sheet_num]))
    {
        $result = count($table_columns[$sheet_num]);
    }

    return $result;
}

?>