<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventory extends CI_Controller
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




	public function view($page = 1, $location_id = 0, $product_id = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['inventory']) || $acl['inventory']->list <= 0)
		{
			redirect(base_url());
		}

		if ($this->_user->location_id > 0)
		{
			$location_id = $this->_user->location_id;
			$this->db->where('location_id', $this->_user->location_id);
		}
		elseif ($location_id > 0)
		{
			$this->db->where('location_id', $location_id);
		}

		if ($product_id > 0)
		{
			$this->db->where('product_id', $product_id);
		}

		if ($this->_user->location_id > 0)
		{
			$this->db->where('location_id', $this->_user->location_id);
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("name");
		$arr_inventory = $this->core_model->get('inventory');

		foreach ($arr_inventory as $inventory)
		{
			$inventory->quantity_display = number_format($inventory->quantity, 0, '.', ',');
		}

		if ($this->_user->location_id > 0)
		{
			$this->db->where('location_id', $this->_user->location_id);
		}
		elseif ($location_id > 0)
		{
			$this->db->where('location_id', $location_id);
		}

		if ($product_id > 0)
		{
			$this->db->where('product_id', $product_id);
		}

		if ($this->_user->location_id > 0)
		{
			$this->db->where('location_id', $this->_user->location_id);
		}

		$count_inventory = $this->core_model->count('inventory');
		$count_page = ceil($count_inventory / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Inventory';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['location_id'] = $location_id;
		$arr_data['product_id'] = $product_id;
		$arr_data['arr_inventory'] = $arr_inventory;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('inventory', $arr_data);
	}




	public function ajax_get($product_id, $location_id)
	{
		$json['status'] = 'success';

		try
		{
			if ($product_id <= 0 || $location_id <= 0)
			{
				throw new Exception();
			}

			$this->db->where('product_id', $product_id);
			$this->db->where('location_id', $location_id);
			$arr_inventory = $this->core_model->get('inventory');

			if (count($arr_inventory) <= 0)
			{
				throw new Exception();
			}

			$inventory = $arr_inventory[0];
			$inventory->quantity_display = number_format($inventory->quantity, 0, '', '');

			$json['inventory'] = $inventory;
		}
		catch (Exception $e)
		{
			$json['message'] = $e->getMessage();
			$json['status'] = 'error';

			if ($json['message'] == '')
			{
				$json['message'] = 'Server error.';
			}
		}

		echo json_encode($json);
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
		$this->db->where('status', 'Active');
		$this->db->order_by('name');
		return $this->core_model->get('product');
	}
}