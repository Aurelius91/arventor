<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report extends CI_Controller
{
	private $_setting;
	private $_user;
	private $_acl;
	private $_has_image;

	public function __construct()
	{
		parent:: __construct();

		$user_id = $this->session->userdata('user_id');

		if ($user_id > 0)
		{
			$this->_user = $this->core_model->get('user', $user_id);
			$this->_setting = $this->setting_model->load();
			$this->_acl = $this->cms_function->generate_acl($this->_user->id);

			$this->_user->address = $this->cms_function->trim_text($this->_user->address);
			$this->_setting->company_address = $this->cms_function->trim_text($this->_setting->company_address);
			$this->_user->image_name = $this->cms_function->generate_image('user', $this->_user->id);

			$this->_has_image = 0;
		}
		else
		{
			redirect(base_url() . 'login/');
		}

		// load cms_excel
		$this->load->library('cms_excel');
	}




	public function report_cashflow($statement_id = 0, $month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_cashflow']) || $acl['report_cashflow']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_statement = $this->_get_statement();
		$statement_id = ($statement_id <= 0) ? $arr_statement[0]->id : $statement_id;

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_cashflow($statement_id, $month, $year);
		$arr_result = $arr_record['arr_result'];

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Report cashflow';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['statement_id'] = $statement_id;
		$arr_data['month'] = $month;
		$arr_data['year'] = $year;
		$arr_data['arr_statement'] = $arr_statement;
		$arr_data['arr_result'] = $arr_result;

		$this->load->view('html', $arr_data);
		$this->load->view('report_cashflow', $arr_data);
	}

	public function report_purchase($month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_purchase']) || $acl['report_purchase']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_purchase($month, $year);
		$arr_result = $arr_record['arr_result'];

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Report Purchase';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['month'] = $month;
		$arr_data['year'] = $year;
		$arr_data['arr_result'] = $arr_result;

		$this->load->view('html', $arr_data);
		$this->load->view('report_purchase', $arr_data);
	}

	public function report_sale($month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_sale']) || $acl['report_sale']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_sale($month, $year);
		$arr_result = $arr_record['arr_result'];

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Report Sale';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['month'] = $month;
		$arr_data['year'] = $year;
		$arr_data['arr_result'] = $arr_result;

		$this->load->view('html', $arr_data);
		$this->load->view('report_sale', $arr_data);
	}

	public function report_stock_card($product_id = 0, $location_id = 0, $month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_stock_card']) || $acl['report_stock_card']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_product = $this->_get_product();

		if ($product_id <= 0)
		{
			$product_id = (count($arr_product) > 0) ? $arr_product[0]->id : 0;
		}

		$location_id = ($location_id <= 0) ? 1 : $location_id;

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_stock_card($product_id, $location_id, $month, $year);
		$arr_result = $arr_record['arr_result'];

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Report Stock Card';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_product'] = $arr_product;
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['location_id'] = $location_id;
		$arr_data['product_id'] = $product_id;
		$arr_data['month'] = $month;
		$arr_data['year'] = $year;
		$arr_data['arr_result'] = $arr_result;

		$this->load->view('html', $arr_data);
		$this->load->view('report_stock_card', $arr_data);
	}




	public function export_cashflow($statement_id = 0, $month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_cashflow']) || $acl['report_cashflow']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_product = $this->_get_product();
		$product_id = (count($arr_product) > 0) ? $arr_product[0]->id : 0;

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_cashflow($statement_id, $month, $year);

		$title = 'Report Cashflow - ' . $arr_record['statement']->name;
		$objPHPExcel = $this->cms_excel->create_excel($title);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Report Cashflow');
		$objPHPExcel->getActiveSheet()->setCellValue('A2', 'Month: ' . date('F Y', $arr_record['timestamp_from']));
		$objPHPExcel->getActiveSheet()->setCellValue('A3', 'Account: ' . $arr_record['statement']->name);
		$this->cms_excel->setbold($objPHPExcel, array('A1', 'A2', 'A3'));
		$this->cms_excel->setmerge($objPHPExcel, array('A1:F1', 'A2:F2', 'A3:F3'));
		$this->cms_excel->setautosize($objPHPExcel, array('A', 'B', 'C', 'D', 'E', 'F'));

		$row = 5;

		$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", 'Date');
		$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", 'Ref Number');
		$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", 'Description');
		$objPHPExcel->getActiveSheet()->setCellValue("D{$row}", 'Debit');
		$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", 'Credit');
		$objPHPExcel->getActiveSheet()->setCellValue("F{$row}", 'Remaining');
		$this->cms_excel->setborder($objPHPExcel, "A{$row}", "F{$row}", '#000');
		$this->cms_excel->setbold($objPHPExcel, array("A{$row}", "B{$row}", "C{$row}", "D{$row}", "E{$row}", "F{$row}"));
		$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}", "E{$row}", "F{$row}"));

		foreach ($arr_record['arr_result'] as $result)
		{
			$row += 1;

			$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $result->date_display);
			$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $result->ref_number);
			$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", $result->description);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("D{$row}", $result->debit_display);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("E{$row}", $result->credit_display);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("F{$row}", $result->amount_display);
			$this->cms_excel->setborder($objPHPExcel, "A{$row}", "F{$row}", '#000');
			$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}", "E{$row}", "F{$row}"));
		}

		$this->cms_excel->download_excel($objPHPExcel, $title);
	}

	public function export_purchase($month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_purchase']) || $acl['report_purchase']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_purchase($month, $year);

		$title = 'Report Purchase';
		$objPHPExcel = $this->cms_excel->create_excel($title);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Report Purchase');
		$objPHPExcel->getActiveSheet()->setCellValue('A2', 'Month: ' . date('F Y', $arr_record['timestamp_from']));
		$this->cms_excel->setbold($objPHPExcel, array('A1', 'A2'));
		$this->cms_excel->setmerge($objPHPExcel, array('A1:G1', 'A2:G2'));
		$this->cms_excel->setautosize($objPHPExcel, array('A', 'B', 'C', 'D', 'E', 'F', 'G'));

		$row = 4;

		$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", 'Date');
		$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", 'Number');
		$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", 'Payment Method');
		$objPHPExcel->getActiveSheet()->setCellValue("D{$row}", 'Total');
		$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", 'Status');
		$objPHPExcel->getActiveSheet()->setCellValue("F{$row}", 'Location');
		$objPHPExcel->getActiveSheet()->setCellValue("G{$row}", 'Vendor');
		$this->cms_excel->setborder($objPHPExcel, "A{$row}", "G{$row}", '#000');
		$this->cms_excel->setbold($objPHPExcel, array("A{$row}", "B{$row}", "C{$row}", "D{$row}", "E{$row}", "F{$row}", "G{$row}"));
		$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}"));

		foreach ($arr_record['arr_result'] as $result)
		{
			$row += 1;

			$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $result->date_display);
			$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $result->number);
			$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", $result->type);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("D{$row}", $result->total_display);
			$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", $result->status);
			$objPHPExcel->getActiveSheet()->setCellValue("F{$row}", $result->location_name);
			$objPHPExcel->getActiveSheet()->setCellValue("G{$row}", $result->vendor_name);
			$this->cms_excel->setborder($objPHPExcel, "A{$row}", "G{$row}", '#000');
			$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}"));
		}

		$this->cms_excel->download_excel($objPHPExcel, $title);
	}

	public function export_sale($month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_sale']) || $acl['report_sale']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_sale($month, $year);

		$title = 'Report Sale';
		$objPHPExcel = $this->cms_excel->create_excel($title);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Report Sale');
		$objPHPExcel->getActiveSheet()->setCellValue('A2', 'Month: ' . date('F Y', $arr_record['timestamp_from']));
		$this->cms_excel->setbold($objPHPExcel, array('A1', 'A2'));
		$this->cms_excel->setmerge($objPHPExcel, array('A1:G1', 'A2:G2'));
		$this->cms_excel->setautosize($objPHPExcel, array('A', 'B', 'C', 'D', 'E', 'F', 'G'));

		$row = 4;

		$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", 'Date');
		$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", 'Number');
		$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", 'Payment Method');
		$objPHPExcel->getActiveSheet()->setCellValue("D{$row}", 'Total');
		$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", 'Status');
		$objPHPExcel->getActiveSheet()->setCellValue("F{$row}", 'Location');
		$objPHPExcel->getActiveSheet()->setCellValue("G{$row}", 'Customer');
		$this->cms_excel->setborder($objPHPExcel, "A{$row}", "G{$row}", '#000');
		$this->cms_excel->setbold($objPHPExcel, array("A{$row}", "B{$row}", "C{$row}", "D{$row}", "E{$row}", "F{$row}", "G{$row}"));
		$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}"));

		foreach ($arr_record['arr_result'] as $result)
		{
			$row += 1;

			$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $result->date_display);
			$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $result->number);
			$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", $result->type);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("D{$row}", $result->total_display);
			$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", $result->status);
			$objPHPExcel->getActiveSheet()->setCellValue("F{$row}", $result->location_name);
			$objPHPExcel->getActiveSheet()->setCellValue("G{$row}", $result->customer_name);
			$this->cms_excel->setborder($objPHPExcel, "A{$row}", "G{$row}", '#000');
			$this->cms_excel->setalign_horizontalright($objPHPExcel, array("D{$row}"));
		}

		$this->cms_excel->download_excel($objPHPExcel, $title);
	}

	public function export_stock_card($product_id = 0, $location_id = 0, $month = 0, $year = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['report_stock_card']) || $acl['report_stock_card']->list <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_product = $this->_get_product();
		$product_id = (count($arr_product) > 0) ? $arr_product[0]->id : 0;

		$location_id = ($location_id <= 0) ? 1 : $location_id;

		$month = ($month == 0) ? date('m', time()) : $month;
		$year = ($year == 0) ? date('Y', time()) : $year;

		$arr_record = $this->_stock_card($product_id, $location_id, $month, $year);

		$title = 'Report Stock Card - ' . $arr_record['product']->name . ' - ' . $arr_record['location']->name;
		$objPHPExcel = $this->cms_excel->create_excel($title);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Report Stock Card');
		$objPHPExcel->getActiveSheet()->setCellValue('A2', 'Month: ' . date('F Y', $arr_record['timestamp_from']));
		$objPHPExcel->getActiveSheet()->setCellValue('A3', 'Product: ' . $arr_record['product']->name);
		$objPHPExcel->getActiveSheet()->setCellValue('A4', 'Location: ' . $arr_record['location']->name);
		$this->cms_excel->setbold($objPHPExcel, array('A1', 'A2', 'A3', 'A4'));
		$this->cms_excel->setmerge($objPHPExcel, array('A1:E1', 'A2:E2', 'A3:E3', 'A4:E4'));
		$this->cms_excel->setautosize($objPHPExcel, array('A', 'B', 'C', 'D', 'E'));

		$row = 6;

		$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", 'Date');
		$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", 'Description');
		$objPHPExcel->getActiveSheet()->setCellValue("C{$row}", 'QTY In');
		$objPHPExcel->getActiveSheet()->setCellValue("D{$row}", 'QTY Out');
		$objPHPExcel->getActiveSheet()->setCellValue("E{$row}", 'QTY Remain');
		$this->cms_excel->setborder($objPHPExcel, "A{$row}", "E{$row}", '#000');
		$this->cms_excel->setbold($objPHPExcel, array("A{$row}", "B{$row}", "C{$row}", "D{$row}", "E{$row}"));
		$this->cms_excel->setalign_horizontalright($objPHPExcel, array("C{$row}", "D{$row}", "E{$row}"));

		foreach ($arr_record['arr_result'] as $result)
		{
			$row += 1;

			$objPHPExcel->getActiveSheet()->setCellValue("A{$row}", $result->date_display);
			$objPHPExcel->getActiveSheet()->setCellValue("B{$row}", $result->description);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("C{$row}", $result->quantity_in_display);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("D{$row}", $result->quantity_out_display);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("E{$row}", $result->quantity_remain_display);
			$this->cms_excel->setborder($objPHPExcel, "A{$row}", "E{$row}", '#000');
			$this->cms_excel->setalign_horizontalright($objPHPExcel, array("C{$row}", "D{$row}", "E{$row}"));
		}

		$this->cms_excel->download_excel($objPHPExcel, $title);
	}




	private function _cashflow($statement_id, $month, $year)
	{
		// get Date From
		$date_from = $year . '-' . $month . '-1';
		$date_to = ($month == 12) ? $year + 1 . '-1-1' : $year . '-' . ($month + 1) . '-1';

		$timestamp_from = strtotime($date_from);
		$timestamp_to = strtotime($date_to);

		$this->db->where('date >=', $timestamp_from);
		$this->db->where('date <', $timestamp_to);
		$this->db->where('statement_id', $statement_id);
		$this->db->order_by('date');
		$arr_transaction = $this->core_model->get('transaction');

		// get old transaction
		$this->db->select('SUM(debit) as debit_sum, SUM(credit) as credit_sum');
		$this->db->where('date <', $timestamp_from);
		$this->db->where('statement_id', $statement_id);
		$arr_old_transaction = $this->core_model->get('transaction');

		$debit_sum = ($arr_old_transaction[0]->debit_sum == null) ? 0 : $arr_old_transaction[0]->debit_sum;
		$credit_sum = ($arr_old_transaction[0]->credit_sum == null) ? 0 : $arr_old_transaction[0]->credit_sum;

		$arr_result = array();

		$result = new stdClass();
		$result->date = $timestamp_from;
		$result->debit = 0;
		$result->credit = 0;
		$result->amount = $debit_sum - $credit_sum;
		$result->description = 'Starting Amount';
		$result->ref_number = '';

		$result->date_display = date('d F Y', $result->date);
		$result->debit_display = number_format($result->debit, 0, ',', '.');
		$result->credit_display = number_format($result->credit, 0, ',', '.');
		$result->amount_display = number_format($result->amount, 0, ',', '.');

		$arr_result[] = clone $result;
		$amount = $result->amount;

		foreach ($arr_transaction as $transaction)
		{
			$transaction->description = '';
			$transaction->ref_number = '';

			if ($transaction->credit_id > 0)
			{
				$transaction->description = $transaction->credit_type . ' - ' . $transaction->credit_name;
				$transaction->ref_number = $transaction->credit_number;
			}
			elseif ($transaction->debit_id > 0)
			{
				$transaction->description = $transaction->debit_type . ' - ' . $transaction->debit_name;
				$transaction->ref_number = $transaction->debit_number;
			}
			elseif ($transaction->purchase_id > 0)
			{
				$transaction->description = 'Purchase from ' . $transaction->vendor_name;
				$transaction->ref_number = $transaction->purchase_number;
			}
			elseif ($transaction->sale_id > 0)
			{
				$transaction->description = 'Sale to ' . $transaction->customer_name;
				$transaction->ref_number = $transaction->sale_number;
			}
			else
			{
				$transaction->description = 'Deposit Starting Amount';
			}

			$result = new stdClass();
			$result->date = $transaction->date;
			$result->debit = $transaction->debit;
			$result->credit = $transaction->credit;
			$result->amount = $amount + ($transaction->debit - $transaction->credit);
			$result->description = $transaction->description;
			$result->ref_number = $transaction->ref_number;

			$result->date_display = date('d F Y', $result->date);
			$result->debit_display = number_format($result->debit, 0, ',', '.');
			$result->credit_display = number_format($result->credit, 0, ',', '.');
			$result->amount_display = number_format($result->amount, 0, ',', '.');

			$arr_result[] = clone $result;
			$amount = $result->amount;
		}

		// get transaction
		$statement = $this->core_model->get('statement', $statement_id);

		$arr_record = array();
		$arr_record['arr_result'] = $arr_result;
		$arr_record['statement'] = $statement;
		$arr_record['timestamp_from'] = $timestamp_from;

		return $arr_record;
	}

	private function _get_location()
	{
		if ($this->_user->location_id > 0)
		{
			$this->db->where('id', $this->_user->location_id);
		}

		$this->db->order_by('name');
		return $this->core_model->get('location');
	}

	private function _get_product()
	{
		$this->db->order_by('name');
		return $this->core_model->get('product');
	}

	private function _get_statement()
	{
		$this->db->order_by('name');
		return $this->core_model->get('statement');
	}

	private function _purchase($month, $year)
	{
		// get Date From
		$date_from = $year . '-' . $month . '-1';
		$date_to = ($month == 12) ? $year + 1 . '-1-1' : $year . '-' . ($month + 1) . '-1';

		$timestamp_from = strtotime($date_from);
		$timestamp_to = strtotime($date_to);

		$this->db->where('date >=', $timestamp_from);
		$this->db->where('date <', $timestamp_to);
		$this->db->order_by('date');
		$arr_purchase = $this->core_model->get('purchase');

		foreach ($arr_purchase as $purchase)
		{
			$purchase->date_display = date('d F Y', $purchase->date);
			$purchase->total_display = number_format($purchase->total, 0, ',', '.');
		}

		$arr_record = array();
		$arr_record['arr_result'] = $arr_purchase;
		$arr_record['timestamp_from'] = $timestamp_from;

		return $arr_record;
	}

	private function _sale($month, $year)
	{
		// get Date From
		$date_from = $year . '-' . $month . '-1';
		$date_to = ($month == 12) ? $year + 1 . '-1-1' : $year . '-' . ($month + 1) . '-1';

		$timestamp_from = strtotime($date_from);
		$timestamp_to = strtotime($date_to);

		$this->db->where('date >=', $timestamp_from);
		$this->db->where('date <', $timestamp_to);
		$this->db->order_by('date');
		$arr_sale = $this->core_model->get('sale');

		foreach ($arr_sale as $sale)
		{
			$sale->date_display = date('d F Y', $sale->date);
			$sale->total_display = number_format($sale->total, 0, ',', '.');
		}

		$arr_record = array();
		$arr_record['arr_result'] = $arr_sale;
		$arr_record['timestamp_from'] = $timestamp_from;

		return $arr_record;
	}

	private function _stock_card($product_id, $location_id, $month, $year)
	{
		// get Date From
		$date_from = $year . '-' . $month . '-1';
		$date_to = ($month == 12) ? $year + 1 . '-1-1' : $year . '-' . ($month + 1) . '-1';

		$timestamp_from = strtotime($date_from);
		$timestamp_to = strtotime($date_to);

		// get stock
		$this->db->where('date >=', $timestamp_from);
		$this->db->where('date <', $timestamp_to);
		$this->db->where('product_id', $product_id);
		$this->db->where('location_id', $location_id);
		$this->db->order_by('date');
		$arr_stock = $this->core_model->get('stock');

		// get stock awal
		$this->db->select('SUM(quantity_in) as qty_in, SUM(quantity_out) as qty_out');
		$this->db->where('date <', $timestamp_from);
		$this->db->where('product_id', $product_id);
		$this->db->where('location_id', $location_id);
		$arr_old_stock = $this->core_model->get('stock');

		$quantity_in = ($arr_old_stock[0]->qty_in == null) ? 0 : $arr_old_stock[0]->qty_in;
		$quantity_out = ($arr_old_stock[0]->qty_out == null) ? 0 : $arr_old_stock[0]->qty_out;

		$result = new stdClass();
		$result->date = $timestamp_from;
		$result->quantity_in = 0;
		$result->quantity_out = 0;
		$result->quantity_remain = $quantity_in - $quantity_out;
		$result->description = 'Starting Stock';

		$result->date_display = date('d F Y', $result->date);

		$result->quantity_in_display = number_format($result->quantity_in, 0, '', '');
		$result->quantity_out_display = number_format($result->quantity_out, 0, '', '');
		$result->quantity_remain_display = number_format($result->quantity_remain, 0, '', '');

		$arr_result[] = clone $result;
		$quantity_remain = $result->quantity_remain;

		foreach ($arr_stock as $stock)
		{
			$result = new stdClass();
			$result->date = $stock->date;
			$result->quantity_in = $stock->quantity_in;
			$result->quantity_out = $stock->quantity_out;
			$result->quantity_remain = $quantity_remain + ($result->quantity_in - $result->quantity_out);
			$result->description = $stock->type . ' - ' . $stock->ref_number;

			$result->date_display = date('d F Y', $result->date);

			$result->quantity_in_display = number_format($result->quantity_in, 0, '', '');
			$result->quantity_out_display = number_format($result->quantity_out, 0, '', '');
			$result->quantity_remain_display = number_format($result->quantity_remain, 0, '', '');

			$arr_result[] = clone $result;
			$quantity_remain = $result->quantity_remain;
		}

		// get product
		$product = $this->core_model->get('product', $product_id);

		// get location
		$location = $this->core_model->get('location', $location_id);

		$arr_record = array();
		$arr_record['arr_result'] = $arr_result;
		$arr_record['product'] = $product;
		$arr_record['location'] = $location;
		$arr_record['timestamp_from'] = $timestamp_from;

		return $arr_record;
	}
}