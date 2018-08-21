<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends CI_Controller
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

			if ($this->_user->type != 'Admin')
			{
				redirect(base_url() . 'pos/');
			}
		}
		else
		{
			redirect(base_url() . 'login/');
		}
	}




	public function index()
	{
		$acl = $this->_acl;

		// get total sales today
		$today = date('Y-m-d', time());

		$date_from = strtotime($today);
		$date_to = $date_from + 86400;

		// get count sales
		$this->db->where('date >=', $date_from);
		$this->db->where('date <', $date_to);
		$this->db->where('draft <=', 0);
		$count_sale = $this->core_model->count('sale');

		// get customer
		$this->db->where('id >', 1);
		$count_customer = $this->core_model->count('customer');

		// get product
		$this->db->where('status', 'Active');
		$count_product = $this->core_model->count('product');

		// get payment
		$earnings = 0;
		$spendings = 0;

		$this->db->where('date >=', $date_from);
		$this->db->where('date <', $date_to);
		$arr_transaction = $this->core_model->get('transaction');

		foreach ($arr_transaction as $transaction)
		{
			$earnings += $transaction->debit;
			$spendings += $transaction->credit;
		}

		$total_earnings = number_format($earnings, 0, ',', '.');
		$total_spendings = number_format($spendings, 0, ',', '.');

		// get all minimum stock
		$this->db->where('quantity <=', $this->_setting->setting__webshop_default_minimum_quantity);

		if ($this->_user->location_id > 0)
		{
			$this->db->where('location_id', $this->_user->location_id);
		}

		$this->db->order_by('quantity');
		$arr_inventory = $this->core_model->get('inventory');

		foreach ($arr_inventory as $inventory)
		{
			$inventory->quantity_display = number_format($inventory->quantity, 0, '', '');
		}

		// get top 10 product
		$this->db->select("COUNT(product_id) as product_count, product_name");
		$this->db->group_by('product_id');
		$this->db->order_by('product_count DESC, product_name');
		$this->db->limit(10);
		$arr_sale_item = $this->core_model->get('sale_item');

		$this->db->select("COUNT(customer_id) as customer_count, customer_name");
		$this->db->group_by('customer_id');
		$this->db->order_by('customer_count DESC, customer_name');
		$this->db->limit(10);
		$arr_sale = $this->core_model->get('sale');

		// get all day sales from last week
		$arr_date = array();
		$arr_analytic = array();

		for ($i = 0; $i < 7; $i++)
		{
			$new_date = $date_from - ($i * 86400);

			$analytics = new stdClass();
			$analytics->date = $new_date;
			$analytics->date_display = date('d M', $new_date);
			$analytics->count_date = 0;

			$arr_analytic[$new_date] = clone $analytics;

			$date_to_weekly = $date_from - ($i * 86400);
		}

		$this->db->select('date, COUNT(date) as count_date');
		$this->db->where('date >=', $date_to_weekly);
		$this->db->where('date <=', $date_from);
		$this->db->group_by('date');
		$arr_weekly_sale = $this->core_model->get('sale');

		foreach ($arr_weekly_sale as $weekly_sale)
		{
			$arr_analytic[$weekly_sale->date]->count_date = $weekly_sale->count_date;
		}

		$arr_data = array();
		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Dashboard';
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['count_sale'] = $count_sale;
		$arr_data['total_earnings'] = $total_earnings;
		$arr_data['total_spendings'] = $total_spendings;
		$arr_data['count_customer'] = $count_customer;
		$arr_data['count_product'] = $count_product;
		$arr_data['arr_inventory'] = $arr_inventory;
		$arr_data['arr_sale_item'] = $arr_sale_item;
		$arr_data['arr_sale'] = $arr_sale;
		$arr_data['arr_analytic'] = $arr_analytic;

		$this->load->view('html', $arr_data);
		$this->load->view('dashboard', $arr_data);
	}
}