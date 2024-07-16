<?php
require_once 'Classes/PHPExcel.php';
$objPHPExcel = new PHPExcel();


$objPHPExcel->getActiveSheet()->mergeCells('B1:H1');

//Add Head column
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', 'comid')
            ->setCellValue('B2', 'comstatus')
            ->setCellValue('C2', 'comdepartment')
            ->setCellValue('D2', 'comtype')
            ->setCellValue('E2', 'combrand');

//Set Column width
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(5);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);


// Write data from MySQL result
$objConnect = mysqli_connect("localhost","root","1234","it") or die("Error Connect to Database");
mysqli_set_charset($objConnect,"utf8");
$strSQL = "SELECT * FROM inventory ORDER BY comid ASC";
$objQuery = mysqli_query($objConnect,$strSQL);



$i = 2;
while($objResult = mysqli_fetch_array($objQuery))
{
	$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $objResult["comid"]);
	$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $objResult["comstatus"]);
	$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $objResult["comdepartment"]);
	$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $objResult["comtype"]);
	$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $objResult["combrand"]);
	
	$i++;
}
mysqli_close($objConnect);



$objPHPExcel->getActiveSheet()->setTitle('Computer');



header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Computer Inventory.xlsx"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>