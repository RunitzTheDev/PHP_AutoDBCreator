<?php
namespace PHP_AutoDBCreator\DataObj;

require_once(dirname(dirname(__FILE__)) . '/server_config.php');

/**
 * @author RunitzTheCat
 * @file RowDAO.php
 * @package \dataObj\RowDAO
 * @brief Represents data access object for a single row \n
 */
class RowDAO {
    /**
     * The Sheet number associated to this RowDAO
     * 
     * @var int $sheetNum
     */
    private $sheetNum;
    
    /**
     * @author RunitzTheCat
     */
    public function __construct($sheetNumber, $DAOData = null)
    {
        $this->sheetNum = $sheetNumber;
        if($DAOData != null)
        {
            foreach($DAOData as $col_name => $col_value)
            {
                $this->$col_name = $col_value;
            }
        }
        else
        {
            foreach(table_column_names($this->sheetNum) as $attribute) {
                $this->$attribute = null;
            }
        }
    }
    
    public function importArray($DAOData)
    {
        foreach($DAOData as $col_name => $col_value)
        {
            $this->$col_name = $col_value;
        }
    }
    
    /**
     * @author RunitzTheCat
     * @return array of entry data
     */
    public function getArray()
    {
        $buf = array();
        foreach(table_column_names($this->sheetNum) as $col_name) {
            $buf[$col_name] = $this->$col_name;
        }
        
        return $buf;
    }
}
?>


