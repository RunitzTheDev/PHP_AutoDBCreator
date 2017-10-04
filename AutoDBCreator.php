<?php
namespace PHP_AutoDBCreator;

require_once (dirname(__FILE__) . "/PHPExcel/Classes/PHPExcel/IOFactory.php");
require_once (dirname(__FILE__) . '/server_config.php');
require_once (dirname(__FILE__) . '/DataObj/RowDAO.php');

use PDO;
use PDOException;
use PHPExcel_IOFactory;
use PHPExcel_Shared_Date;
use PHP_AutoDBCreator\DataObj\RowDAO as RowDAO;

/**
 * @author RunitzTheCat
 * @file AutoDBCreator.php
 * @package \dataObj\AutoDBCreator
 * @brief Interface for AutoDBCreator \n
 */
interface AutoDBCreate {
    
    /**
     *@author RunitzTheCat
     *@param int $index
     *@return string $sql_code
     */
    public function get_row($sheet_num = 0, $index);
    
    /**
     *@author RunitzTheCat
     *@param int $index
     *@return string $sql_code
     */
    public function delete_row($sheet_num = 0, $index);
    
    /**
     * @author RunitzTheCat
     * @return string $sql_code
     */
    public function get_all($sheet_num = 0);
    
    /**
     *@author RunitzTheCat
     *@param $RowDAO
     *@return string $sql_code
     */
    public function insert(RowDAO $RowDAO, $sheet_num);
    
    /**
     *@author RunitzTheCat
     *@param $RowDAO, int $index
     *@return string $sql_code
     */
    public function update($sheet_num, $RowDAO, $index);
    
    /**
     * @author RunitzTheCat
     * @param $uploaded_file
     * @return string $sql_code
     */
    public function submit_file($uploaded_file);
}


/**
 * @author RunitzTheCat
 * @file AutoDBCreator.php
 * @package \dataObj\AutoDBCreator
 * @brief Class that handles submission of SQL to database \n
 */
class AutoDBCreator implements AutoDBCreate
{
    /**
     * Database Host IP Address
     *
     * @var string $DBHOST
     */
    private $DBHOST;
    
    /**
     * Database Authentication ID
     *
     * @var string $DBID
     */
    private $DBID;
    
    /**
     * Database Authentication Password
     *
     * @var string $DBPW
     */
    private $DBPW;
    
    /**
     * Database Name
     *
     * @var string $DBNAME
     */
    private $DBNAME;
    
    /**
     * Database Char Set
     *
     * @var string $DBCHARSET
     */
    private $DBCHARSET;
    
    /**
     * Replacment string for Spaces
     *
     * @var string $replaceSpace
     */
    private $replaceSpace;
    
    /**
     * Boolean Indicator for Lowercase Column names and Table Name
     *
     * @var bool $lowercase
     */
    private $lowercase;
    
    /**
     * String representing date format
     *
     * @var string $dateFormat
     */
    private $dateFormat;
    
    /**
     *@author RunitzTheCat
     */
    public function __construct($replaceSpaces = "_", $lower = true, $dateFormatString = "Y-m-d")
    {
        $this->DBHOST = server_configs()['dbhost'];
        $this->DBID = server_configs()['dbid'];
        $this->DBPW = server_configs()['dbpw'];
        $this->DBNAME = server_configs()['dbname'];
        $this->DBCHARSET = server_configs()['charset'];
        $this->replaceSpace = $replaceSpaces;
        $this->lowercase = $lower;
        $this->dateFormat = $dateFormatString;
    }
    
    //SECTION - PDO QUERY SUBMISSION FUNCTIONS//
    /**
     * Submits an SQL Query through PDO
     * 
     *@author RunitzTheCat
     *@param string $sql_code
     *@return \PDOStatement $retval
     */
    private function submitSql($sql_code)
    {
        //Create connection
        $dsn = "mysql:host=" . $this->DBHOST . ";dbname=" . $this->DBNAME . ";charset=" . $this->DBCHARSET;
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, $this->DBID, $this->DBPW, $opt);
        $pdoErrorMsg = "";
        
        //Use transactions for reliability and speed
        $pdo->beginTransaction();
        
        //Prepare statement to eliminate SQL Injections
        $stmt = $pdo->prepare($sql_code);
        
        try {
            //Execute $sql_code
            $pdo->execute($stmt);
        } catch (PDOException $e){
            //Rollback on fail
            $pdo->rollBack();
            $pdoErrorMsg = $e->getMessage();
        }
        
        $retval = $pdo->commit();
        
        //No return value (from query fail)
        if(! $retval ) {
            die('Could not enter data: ' . $pdoErrorMsg);
        }
        
        $stmt = null;
        $pdo = null;
        
        return $retval;
    }
    
    
    //SECTION - OPTION MANAGEMENT//
    private function lowerChars($string)
    {
        $result = '';
        if($this->lowercase)
        {
            $result .= strtolower($string);
        }
        else 
        {
            $result .= $string;
        }
        $result = str_replace(" ", $this->replaceSpace, $result);
        return $result;
    }
    
    private function literalChars($string)
    {
        $result = $string;
        if($result === NULL) {
            $result = "0";
        }
        $result = str_replace("\n", "", $result);
        $result = str_replace("\t", "", $result);
        $result = str_replace("'", "\'", $result);
        $result = str_replace('"', '\"', $result);
        return $result;
    }
    
    
    private function translateExcelCode($string)
    {
        if(strpos($string,'VLOOKUP'))
        {
            $matches = [];
            $result = preg_match('/=VLOOKUP\(([^;]+);([^;]+);([^;]+);([^;]+)\)/', $string, $matches, 256);
            return "NA";
        }
        return $string;
    }
    
    private function create_db_table($sheet_num) {
        //Tracks columns already done to avoid duplicates
        $completed_columns = [];
        
        $sql_code = "CREATE TABLE ".get_dt_name($sheet_num)." ( id int NOT NULL AUTO_INCREMENT";
        foreach(table_column_names($sheet_num) as $col_name)
        {
            if(!in_array($col_name, $completed_columns))
            {
                if(strpos($col_name, 'date'))
                {
                    $sql_code .= ", `" . $this->literalChars($col_name) . "` varchar(255)";
                }
                else 
                {
                    $sql_code .= ", `" . $this->literalChars($col_name) . "` varchar(255)";
                }
            }
            $completed_columns[] = $col_name;
        }
        $sql_code .= ", PRIMARY KEY (id) );";
        return $sql_code;
    }
    
    
    //SECTION - SQL Builder Functions//
    /**
     *@author RunitzTheCat
     *@param int $index
     *@return string $sql_code
     */
    public function get_row($sheet_num = 0, $index)
    {
        return "SELECT * FROM ". get_dt_name($sheet_num) ." WHERE id='$index'";
    }
    
    /**
     *@author RunitzTheCat
     *@param int $index
     *@return string $sql_code
     */
    public function delete_row($sheet_num = 0, $index)
    {
        return "DELETE from ". get_dt_name($sheet_num) ." WHERE id='$index'";
    }
    
    /**
     *@author RunitzTheCat
     *@return string $sql_code
     */
    public function get_all($sheet_num = 0)
    {
        return "SELECT * from " . get_dt_name($sheet_num);
    }
    
    
    /**
     *@author RunitzTheCat
     *@param $RowDAO
     *@return string $sql_code
     */
    public function insert(RowDAO $RowDAO, $sheet_num) {
        $col_names = "";
        $col_values = "";
        $first = true;
        
        //Build $col_names and $col_values to plug into query
        foreach($RowDAO->getArray() as $col_name => $col_value)
        {
            if($first)
            {
                $col_names .= "`$col_name`";
                $col_values .= "'$col_value'";
                $first = false;
            }
            else
            {
                $col_names .= ", `$col_name`";
                $col_values .= ", '$col_value'";
            }

        }
        
        //Insert new entry
        $sql_code = "INSERT INTO ". get_dt_name($sheet_num) ." (". $col_names .") VALUES (". $col_values .");";
        return $sql_code;
    }
    
    /**
     *@author RunitzTheCat
     *@param array $RowDAOs
     *@return string $sql_code
     */
    public function multiple_insert($sheet_num, array $RowDAOs) {
        $col_names = "";
        $col_values = "";
        $first = true;
        
        //Reading through each row, O(N**2) for each data value
        foreach($RowDAOs as $RowDAO)
        {
            if($first)
            {
                $first_col = true;
                $col_values .= "(";
                //Build $col_names and $col_values to plug into query
                foreach($RowDAO->getArray() as $col_name => $col_value)
                {
                    if($first_col)
                    {
                        $col_names .= "`$col_name`";
                        $col_values .= "'$col_value'";
                        $first_col = false;
                    }
                    else
                    {
                        $col_names .= ", `$col_name`";
                        $col_values .= ", '$col_value'";
                    }
                    
                }
                $col_values .= ")";
                $first = false;
            }
            else 
            {
                $first_col = true;
                $col_values .= ",(";
                //Build $col_names and $col_values to plug into query
                foreach($RowDAO->getArray() as $col_name => $col_value)
                {
                    if($first_col)
                    {
                        $col_values .= "'$col_value'";
                        $first_col = false;
                    }
                    else
                    {
                        $col_values .= ", '$col_value'";
                    }
                    
                }
                $col_values .= ")";
            }

        }
        
        //Insert new entry
        $sql_code = "INSERT INTO ". get_dt_name($sheet_num) ." (". $col_names .") VALUES ". $col_values .";";
        return $sql_code;
    }
    
    /**
     *@author RunitzTheCat
     *@param $RowDAO, int $index
     *@return string $sql_code
     */
    public function update($sheet_num, $RowDAO, $index) {
        $attr = "";
        $first = true;
        
        //Build $col_names and $col_values to plug into query
        
        foreach($RowDAO->getArray() as $col_name => $col_value)
        {
            if($first)
            {
                $attr = "`$col_name`=`$col_value`";
                $first = false;
            }
            else
            {
                $attr .= ", `$col_name`=`$col_value`";
            }
            
        }
        
        //Update where email=$savedEmail
        $sql_code = "UPDATE ". get_dt_name($sheet_num) ." SET ". $attr ." WHERE id='". $index ."'";
        return $sql_code;
    }
    
    /**
     * @author RunitzTheCat
     * @param string path to $uploaded_file
     * @return array $result
     */
    public function submit_file($uploaded_file, $sheetStart = 0, $sheetEnd = -1)
    {
        //Uses PHPExcel Library; Take the uploaded file and create PHPExcel Class
        $inputFileType = PHPExcel_IOFactory::identify($uploaded_file);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(false);
        $objReader = $objReader->load($uploaded_file);

        $result = array();
        $sheet_count = $objReader->getSheetCount();
        if($sheetEnd > 0 && $sheetEnd < $sheet_count)
        {
            $sheet_count = $sheetEnd;
        }
        //Reads each sheet of the file
        for($sheet_num = $sheetStart; $sheet_num < $sheet_count; ++$sheet_num)
        {
            $dbRows = array();
            $sheetArry = array();
            $objReader->setActiveSheetIndex($sheet_num);
            $sheet = $objReader->getActiveSheet();
            $row_num = 0;
            
            $create_new_table = true;
            //If Sheet Title != Table Title, set_dt_name()
            if(get_dt_name($sheet_num) == NULL)
            {
                set_dt_name($sheet_num, $this->literalChars($this->lowerChars($sheet->getTitle())));
            }
            
            $row_width = -1;
            //Reads each row of the sheet
            foreach ($sheet->getRowIterator() as $row)
            {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $col_num = 0;
                $colNames = [];
                $col_values = [];
                //Whether first row will be saved as column title or not
                $set_cols = false;
                //Columns already inputted (avoids duplicates)
                $completed_columns = [];
                $row_empty = true;
                //Reads each cell of the row
                foreach($cellIterator as $cell)
                {
                    if($row_width != -1 && $col_num >= $row_width) {
                        break;
                    }
                    //If creating a new table, grab first row as column titles
                    if(!table_column_count($sheet_num) && $row_num == 0)
                    {
                        if($cell->getValue() === NULL || $cell->getValue() === '')
                        {
                            $row_width = $col_num;
                            break;
                        }
                        $row_empty = false;
                        $colNames[] = $this->literalChars($this->lowerChars($cell->getValue()));
                        $set_cols = true;
                        //Eliminate duplicate entry of column names
                        $completed_columns[] = $this->literalChars($this->lowerChars($cell->getValue()));
                    }
                    else if(!in_array(table_column_names($sheet_num)[$col_num], $completed_columns)) {
                        if($row_num == 0)
                        {
                            $row_width = table_column_count($sheet_num);
                        }
                        //Operation per cell
                        if(PHPExcel_Shared_Date::isDateTime($cell))
                        {
                            $row_empty = false;
                            $col_values[table_column_names($sheet_num)[$col_num]] = date($this->dateFormat, PHPExcel_Shared_Date::ExcelToPHP($cell->getValue()));
                        }
                        else if(!($cell->getValue() === NULL  || $cell->getValue() === '') || !$row_empty)
                        {
                            $row_empty = false;
                            $col_values[table_column_names($sheet_num)[$col_num]] = $this->translateExcelCode($this->literalChars($cell->getValue()));
                        }
                        $completed_columns[] = table_column_names($sheet_num)[$col_num];
                    }
                    $col_num += 1;
                }
                //If empty row encountered, Break Loop
                if($row_empty)
                {
                    break;
                }
                //Saves the column titles
                if($set_cols) {
                    set_table_columns($sheet_num, $colNames);
                    $set_cols = false;
                }

                //Create the table with given sheet title and column titles
                if($create_new_table)
                {
                    $this->submitSql($this->create_db_table($sheet_num));
                    $create_new_table = false;
                }
                //Or insert as Data
                else {
                    $sheetArry[] = $col_values;
                    $tempDAO = new RowDAO($sheet_num, $col_values);
                    if($row_num % 1024 != 1)
                    {
                        $dbRows[] = $tempDAO;
                    }
                    else 
                    {
                        if(count($dbRows))
                        {
                            $this->submitSql($this->multiple_insert($sheet_num, $dbRows));
                        }
                        $dbRows = array($tempDAO);
                    }
                }
                $row_num += 1;
            }
            if(count($dbRows))
            {
                $this->submitSql($this->multiple_insert($sheet_num, $dbRows));
            }
            $result[] = $sheetArry;
        }
        //Send final query list; FAILS will halt queries and cause rollback (SEE ABOVE)
        return $result;
    }
    
}
?>


