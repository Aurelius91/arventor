<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cms_excel
{
	public function create_excel($title)
	{
		$CI = &get_instance();

		$CI->load->helper('download');

		$CI->load->library('../third_party/PHPExcel');
		$CI->load->library('../third_party/PHPExcel/IOFactory');

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle($title);

		return $objPHPExcel;
	}

	public function download_excel($objPHPExcel, $title)
	{
		$objWriter = IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save("tmp/{$title}.xls");
		$data = file_get_contents ("tmp/{$title}.xls");
		unlink("tmp/{$title}.xls");
		force_download("{$title}.xls", $data);
	}

	public function setautosize($objPHPExcel, $arr_column = array())
	{
		foreach ($arr_column as $column)
		{
			$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(TRUE);
		}
	}

	public function setbold($objPHPExcel, $arr_cell = array())
	{
		foreach ($arr_cell as $cell)
		{
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getFont()->setBold(TRUE);
		}
	}

	public function setborder($objPHPExcel, $cell_from, $cell_to, $colour)
	{
		$objPHPExcel->getActiveSheet()->getStyle("{$cell_from}:{$cell_to}")->getBorders()->applyFromArray(
			array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => array('rgb' => $colour)
				)
			)
		);
	}

	public function setalign_horizontalright($objPHPExcel, $arr_cell = array())
	{
		foreach ($arr_cell as $cell)
		{
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		}
	}

	public function setmerge($objPHPExcel, $arr_range)
	{
		foreach ($arr_range as $range)
		{
			$objPHPExcel->getActiveSheet()->mergeCells($range);
		}
	}

	public function setnumberformat($objPHPExcel, $arr_cell = array())
	{
		foreach ($arr_cell as $cell)
		{
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		}
	}

	public function setalign_verticalcenter($objPHPExcel, $arr_cell = array())
	{
		foreach ($arr_cell as $cell)
		{
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		}
	}
}
