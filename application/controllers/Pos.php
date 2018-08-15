<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pos extends CI_Controller
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
	}




	public function index()
	{
		$acl = $this->_acl;

		$date_display = date('Y-m-d', time());
		$location_id = ($this->_user->location_id > 0) ? $this->_user->location_id : $this->_setting->setting__webshop_default_pos_location_id;

		if ($location_id <= 0)
		{
			$this->db->order_by();
		}

		// get product list
		$this->db->where('location_id', $location_id);
		$this->db->order_by('product_name');
		$arr_inventory = $this->core_model->get('inventory');
		$arr_product_id = $this->cms_function->extract_records($arr_inventory, 'product_id');

		$arr_product = $this->core_model->get('product', $arr_product_id);
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$product->price_display = number_format($product->price, 0, ',', '.');
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_inventory as $inventory)
		{
			$inventory->quantity_display = number_format($inventory->quantity, 0, '', '');
			$inventory->product_price = (isset($arr_product_lookup[$inventory->product_id])) ? $arr_product_lookup[$inventory->product_id]->price : 0;
			$inventory->product_price_display = (isset($arr_product_lookup[$inventory->product_id])) ? $arr_product_lookup[$inventory->product_id]->price_display : 0;
			$inventory->product_barcode = (isset($arr_product_lookup[$inventory->product_id])) ? $arr_product_lookup[$inventory->product_id]->barcode : '';
		}

		$arr_data = array();
		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'POS';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['location_id'] = $location_id;
		$arr_data['arr_inventory'] = $arr_inventory;

		$this->load->view('html', $arr_data);
		$this->load->view('pos', $arr_data);
	}
}