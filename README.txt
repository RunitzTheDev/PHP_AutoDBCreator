***PHP AutoDBCreator***
By RunitzTheDev

***What does it do?***
Tired of writing out column names in database tables when uploading your Excel files? Look no further! PHP AutoDBCreator will automatically create database tables, upload all data, and immediately return an array representing all data for any Csv or Excel (or any file type supported by PHPExcel) files! Simply import the "AutoDBCreator" class and call "submit_file($path)" to instantly get the array representation while simultaneously uploading to a database according to "server_config.php"’s configurations!

*This software utilizes the PHPExcel library. Credits where they are due.
	+Check out "https://github.com/PHPOffice/PHPExcel" for more info!


***IMPORTANT CAVEATS***
	-The first row of the data is recognized as Column Names unless custom column names are set
	-Columns with duplicate names are removed
	-If using custom column names in a sheet with duplicate column titles, providing different names for duplicate columns will cause those columns to be not ignored
	-Any empty cell in the column name row will cause further columns to be ignored
	-Any row that is completely empty will cause further rows to be ignored


***Documentation***
+Installation: Clone this repository into your desired location. Include the AutoDBCreator class which uses namespace "PHP_AutoDBCreator\AutoDBCreator".


***EXAMPLE USE***
use PHP_AutoDBCreator\AutoDBCreator as AutoDBCreator;

$example = new AutoDBCreator($replaceSpaces, $lower, $dateFormatString);
$3dArray = $example->submit_file($file_path);

***EXAMPLE END***


The class takes 3 optional parameters:
	+$replaceSpaces: the string used to replace spaces in Column Names and Table Names
	+$lower: Boolean Indicator of whether you wish the Column Names and Table Names are in lower case
	+$dateFormatString: the string indicating the format of any Date values (please refer to PHPExcel SharedDate documentation for available options)

Function:
	+submit_file($path): returns a 3-dimensional array representing the excel file (sheet, row, cell)


CONFIGURABLE FILES:
+server_config.php - Used to configure the following:
	-Server Host IP
	-Database ID
	-Database PW
	-Database Name
	-Table Column Names: Set an array of column names for a sheet number to the same index number; AutoDBCreator will then use these names instead of the given titles in Excel