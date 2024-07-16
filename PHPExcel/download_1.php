<?php
require_once 'Classes/PHPExcel.php';
$objPHPExcel = new PHPExcel();


$objPHPExcel->getActiveSheet()->mergeCells('B1:H1');
$objPHPExcel->getActiveSheet()->setCellValue('B1','test');

//Add Head column
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', 'ID :')
            ->setCellValue('B2', 'Date')
            ->setCellValue('C2', 'Topic')
            ->setCellValue('D2', 'Category')
            ->setCellValue('E2', 'Complaint Person');

//Set Column width
//$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
//$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
//$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(5);
//$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
//$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);


// Write data from MySQL result
$objConnect = mysqli_connect("localhost","root","1234","saleecolour") or die("Error Connect to Database");
mysqli_set_charset($objConnect,"utf8");
$strSQL = "SELECT * FROM complaint_main";
$objQuery = mysqli_query($objConnect,$strSQL);



$i = 2;
while($objResult = mysqli_fetch_array($objQuery))
{
	$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $objResult["cp_no"]);
	$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $objResult["cp_date"]);
	$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $objResult["cp_topic"]);
	$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $objResult["cp_topic_cat"]);
	$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $objResult["cp_user_name"]);
	
	
}
mysqli_close($objConnect);



$objPHPExcel->getActiveSheet()->setTitle('Computer');



header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Computer Inventory.xlsx"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>