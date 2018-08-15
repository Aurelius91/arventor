<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Location extends CI_Controller
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




	public function add()
	{
		$acl = $this->_acl;

		if (!isset($acl['location']) || $acl['location']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Location';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('location_add', $arr_data);
	}

	public function edit($location_id = 0)
	{
		$acl = $this->_acl;

		if ($location_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['location']) || $acl['location']->edit <= 0)
		{
			redirect(base_url());
		}

		$location = $this->core_model->get('location', $location_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Location';
		$arr_data['location'] = $location;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('location_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['location']) || $acl['location']->list <= 0)
		{
			redirect(base_url());
		}

		$query = ($query != '') ? base64_decode($query) : '';

		if ($query != '')
		{
			$this->db->like('name', $query);
		}

		if ($filter == 'all')
		{
			$this->db->like('name', $query);
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("name");
		$arr_location = $this->core_model->get('location');

		if ($query != '')
		{
			$this->db->like('name', $query);
		}

		if ($filter == 'all')
		{
			$this->db->like('name', $query);
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$count_location = $this->core_model->count('location');
		$count_page = ceil($count_location / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Location';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_location'] = $arr_location;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('location', $arr_data);
	}




	public function ajax_add()
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['location']) || $acl['location']->add <= 0)
			{
				throw new Exception('You have no access to add location. Please contact your administrator.');
			}

			$location_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$location_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$location_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($location_record['name']));
            $location_record['url_name'] = preg_replace("/[\s_]/", "-", $location_record['url_name']);

			$this->_validate_add($location_record);

			$location_id = $this->core_model->insert('location', $location_record);
			$location_record['id'] = $location_id;
			$location_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($location_record['id'], 'add', $location_record, array(), 'location');

			$this->_add_inventory($location_id, $location_record);

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('color_id' => $color_id));
			}

			$json['location_id'] = $location_id;

			$this->db->trans_complete();
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

	public function ajax_change_status($location_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($location_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['location']) || $acl['location']->edit <= 0)
			{
				throw new Exception('You have no access to edit location. Please contact your administrator.');
			}

			$old_location = $this->core_model->get('location', $location_id);

			$old_location_record = array();

			foreach ($old_location as $key => $value)
			{
				$old_location_record[$key] = $value;
			}

			$location_record = array();

			foreach ($_POST as $k => $v)
			{
				$location_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('location', $location_id, $location_record);
			$location_record['id'] = $location_id;
			$location_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($location_id, 'status', $location_record, $old_location_record, 'location');

			$this->db->trans_complete();
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

	public function ajax_delete($location_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($location_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['location']) || $acl['location']->delete <= 0)
			{
				throw new Exception('You have no access to delete location. Please contact your administrator.');
			}

			$location = $this->core_model->get('location', $location_id);
			$updated = $_POST['updated'];
			$location_record = array();

			foreach ($location as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$location_record[$k] = $v;
				}
			}

			$this->_validate_delete($location_id);

			$this->core_model->delete('location', $location_id);
			$location_record['id'] = $location->id;
			$location_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($location_record['id'], 'delete', $location_record, array(), 'location');

			$this->_delete_inventory($location_id);

			if ($this->_has_image > 0)
			{
				$this->db->where('location_id', $location_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->db->trans_complete();
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

	public function ajax_edit($location_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['location']) || $acl['location']->edit <= 0)
			{
				throw new Exception('You have no access to edit location. Please contact your administrator.');
			}

			$old_location = $this->core_model->get('location', $location_id);

			$old_location_record = array();

			foreach ($old_location as $key => $value)
			{
				$old_location_record[$key] = $value;
			}

			$location_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$location_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$location_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($location_record['name']));
            $location_record['url_name'] = preg_replace("/[\s_]/", "-", $location_record['url_name']);

			$this->_validate_edit($location_id, $location_record);

			$this->core_model->update('location', $location_id, $location_record);
			$location_record['id'] = $location_id;
			$location_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($location_record['id'], 'edit', $location_record, $old_location_record, 'location');
			$this->cms_function->update_foreign_field(array('adjustment', 'adjustment_item', 'inventory', 'movement', 'movement_item', 'purchase', 'purchase_item', 'receive', 'receive_item', 'sale', 'sale_item', 'stock', 'user'), $location_record, 'location');

			if ($image_id > 0)
            {
                $this->db->where('color_id', $color_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('color_id' => $color_id));
            }

			$this->db->trans_complete();
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

	public function ajax_get($location_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($location_id <= 0)
			{
				throw new Exception();
			}

			$location = $this->core_model->get('location', $location_id);

			$json['location'] = $location;
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




	private function _add_inventory($location_id, $location_record)
	{
		// get all location
		$arr_product = $this->core_model->get('product');

		foreach ($arr_product as $product)
		{
			$inventory_record = array();

			$inventory_record['location_id'] = $location_id;
			$inventory_record['product_id'] = $product->id;

			$inventory_record['location_type'] = (isset($location_record['type'])) ? $location_record['type'] : '';
			$inventory_record['location_number'] = (isset($location_record['number'])) ? $location_record['number'] : '';
			$inventory_record['location_name'] = (isset($location_record['name'])) ? $location_record['name'] : '';
			$inventory_record['location_date'] = (isset($location_record['date'])) ? $location_record['date'] : '';
			$inventory_record['location_status'] = (isset($location_record['status'])) ? $location_record['status'] : '';

			$inventory_record['product_type'] = $product->type;
			$inventory_record['product_number'] = $product->number;
			$inventory_record['product_name'] = $product->name;
			$inventory_record['product_date'] = $product->date;
			$inventory_record['product_status'] = $product->status;

			$this->core_model->insert('inventory', $inventory_record);
		}
	}

	private function _delete_inventory($location_id)
	{
		$this->db->where('location_id', $location_id);
		$arr_inventory = $this->core_model->get('inventory');

		$found = 0;

		foreach ($arr_inventory as $inventory)
		{
			if ($inventory->quantity > 0)
			{
				$found += 1;
			}
		}

		if ($found > 0)
		{
			throw new Exception('Data cannot be deleted because it has stock in the inventory');
		}

		$this->db->where('location_id', $location_id);
		$this->core_model->delete('inventory');
	}

	private function _validate_add($location_record)
	{
		$this->db->where('name', $location_record['name']);
		$count_location = $this->core_model->count('location');

		if ($count_location > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($location_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $location_id);
		$count_location = $this->core_model->count('location');

		if ($count_location > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count adjustment
		$this->db->where('location_id', $location_id);
		$count_adjustment = $this->core_model->count('adjustment');

		if ($count_adjustment > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count movement
		$this->db->where('location_id', $location_id);
		$count_movement = $this->core_model->count('movement');

		if ($count_movement > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count inventory
		$this->db->where('location_id', $location_id);
		$count_inventory = $this->core_model->count('inventory');

		if ($count_inventory > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count stock
		$this->db->where('location_id', $location_id);
		$count_stock = $this->core_model->count('stock');

		if ($count_stock > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count purchase
		$this->db->where('location_id', $location_id);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count sale
		$this->db->where('location_id', $location_id);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Data cannot be deleted');
		}
	}

	private function _validate_edit($location_id, $location_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $location_id);
		$count_location = $this->core_model->count('location');

		if ($count_location > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $location_id);
		$this->db->where('name', $location_record['name']);
		$count_location = $this->core_model->count('location');

		if ($count_location > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}