<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class movement extends CI_Controller
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

		if (!isset($acl['movement']) || $acl['movement']->add <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'movement';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('movement_add', $arr_data);
	}

	public function edit($movement_id = 0)
	{
		$acl = $this->_acl;

		if ($movement_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['movement']) || $acl['movement']->edit <= 0)
		{
			redirect(base_url());
		}

		$movement = $this->core_model->get('movement', $movement_id);
		$movement->date_display = date('Y-m-d', $movement->date);

		$this->db->where('movement_id', $movement->id);
		$arr_movement_item = $this->core_model->get('movement_item');

		foreach ($arr_movement_item as $movement_item)
		{
			$movement_item->quantity_display = number_format($movement_item->quantity, 0, '', '');
		}

		$movement->arr_movement_item = $arr_movement_item;

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'movement';
		$arr_data['movement'] = $movement;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('movement_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['movement']) || $acl['movement']->list <= 0)
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
			$this->db->or_like('number', $query);
			$this->db->or_like('location_name', $query);
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("date DESC");
		$arr_movement = $this->core_model->get('movement');

		foreach ($arr_movement as $movement)
		{
			$movement->date = date('d F Y', $movement->date);
		}

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

		$count_movement = $this->core_model->count('movement');
		$count_page = ceil($count_movement / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'movement';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_movement'] = $arr_movement;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('movement', $arr_data);
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

			if (!isset($acl['movement']) || $acl['movement']->add <= 0)
			{
				throw new Exception('You have no access to add movement. Please contact your administrator.');
			}

			$movement_record = array();
			$arr_movement_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'movement_item_movement_item')
				{
					$arr_movement_item = json_decode($v);
				}
				else
				{
					$movement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$movement_record = $this->cms_function->populate_foreign_field($movement_record['location_id'], $movement_record, 'location');

			$location_to = $this->core_model->get('location', $movement_record['location_to_id']);
			$movement_record['location_to_type'] = $location_to->type;
			$movement_record['location_to_number'] = $location_to->number;
			$movement_record['location_to_name'] = $location_to->name;
			$movement_record['location_to_date'] = $location_to->date;
			$movement_record['location_to_status'] = $location_to->status;

			$this->_validate_add($movement_record);

			$movement_id = $this->core_model->insert('movement', $movement_record);
			$movement_record['id'] = $movement_id;
			$movement_record['last_query'] = $this->db->last_query();

			if (!isset($movement_record['number']) || (isset($movement_record['number']) && $movement_record['number'] == ''))
			{
				$movement_record['number'] = '#MOV' . str_pad($movement_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('movement', $movement_id, array('number' => $movement_record['number']));
			}

			$movement_record['name'] = $movement_record['number'];

			$this->cms_function->system_log($movement_record['id'], 'add', $movement_record, array(), 'movement');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('color_id' => $color_id));
			}

			$this->_add_movement_item($movement_id, $movement_record, $arr_movement_item);

			$json['movement_id'] = $movement_id;

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

	public function ajax_change_status($movement_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($movement_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['movement']) || $acl['movement']->edit <= 0)
			{
				throw new Exception('You have no access to edit movement. Please contact your administrator.');
			}

			$old_movement = $this->core_model->get('movement', $movement_id);

			$old_movement_record = array();

			foreach ($old_movement as $key => $value)
			{
				$old_movement_record[$key] = $value;
			}

			$movement_record = array();

			foreach ($_POST as $k => $v)
			{
				$movement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('movement', $movement_id, $movement_record);
			$movement_record['id'] = $movement_id;
			$movement_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($movement_id, 'status', $movement_record, $old_movement_record, 'movement');

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

	public function ajax_delete($movement_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($movement_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['movement']) || $acl['movement']->delete <= 0)
			{
				throw new Exception('You have no access to delete movement. Please contact your administrator.');
			}

			$movement = $this->core_model->get('movement', $movement_id);
			$updated = $_POST['updated'];
			$movement_record = array();

			foreach ($movement as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$movement_record[$k] = $v;
				}
			}

			$this->_validate_delete($movement_id);

			$this->core_model->delete('movement', $movement_id);
			$movement_record['id'] = $movement->id;
			$movement_record['last_query'] = $this->db->last_query();
			$movement_record['name'] = $movement_record['number'];

			$this->cms_function->system_log($movement_record['id'], 'delete', $movement_record, array(), 'movement');

			if ($this->_has_image > 0)
			{
				$this->db->where('movement_id', $movement_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->_delete_movement_item($movement_id);

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

	public function ajax_edit($movement_id)
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

			if (!isset($acl['movement']) || $acl['movement']->edit <= 0)
			{
				throw new Exception('You have no access to edit movement. Please contact your administrator.');
			}

			$old_movement = $this->core_model->get('movement', $movement_id);

			$old_movement_record = array();

			foreach ($old_movement as $key => $value)
			{
				$old_movement_record[$key] = $value;
			}

			$movement_record = array();
			$arr_movement_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'movement_item_movement_item')
				{
					$arr_movement_item = json_decode($v);
				}
				else
				{
					$movement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$movement_record = $this->cms_function->populate_foreign_field($movement_record['location_id'], $movement_record, 'location');

			$location_to = $this->core_model->get('location', $movement_record['location_to_id']);
			$movement_record['location_to_type'] = $location_to->type;
			$movement_record['location_to_number'] = $location_to->number;
			$movement_record['location_to_name'] = $location_to->name;
			$movement_record['location_to_date'] = $location_to->date;
			$movement_record['location_to_status'] = $location_to->status;

			$this->_validate_edit($movement_id, $movement_record);

			$this->core_model->update('movement', $movement_id, $movement_record);
			$movement_record['id'] = $movement_id;
			$movement_record['last_query'] = $this->db->last_query();
			$movement_record['name'] = $movement_record['number'];

			$this->cms_function->system_log($movement_record['id'], 'edit', $movement_record, $old_movement_record, 'movement');

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

            $this->_update_movement_item($movement_id, $movement_record, $arr_movement_item);

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

	public function ajax_get($movement_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($movement_id <= 0)
			{
				throw new Exception();
			}

			$movement = $this->core_model->get('movement', $movement_id);

			$json['movement'] = $movement;
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




	private function _add_movement_item($movement_id, $movement_record, $arr_movement_item)
	{
		// get product
		$arr_product = $this->core_model->get('product');
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_movement_item as $movement_item)
		{
			$movement_item_record = array();
			$movement_item_record['location_id'] = $movement_item->location_id;
			$movement_item_record['location_to_id'] = $movement_item->location_to_id;
			$movement_item_record['movement_id'] = $movement_id;
			$movement_item_record['product_id'] = $movement_item->product_id;

			$movement_item_record['quantity'] = $movement_item->quantity;

			$movement_item_record['movement_type'] = (isset($movement_record['type'])) ? $movement_record['type'] : '';
			$movement_item_record['movement_number'] = (isset($movement_record['number'])) ? $movement_record['number'] : '';
			$movement_item_record['movement_name'] = '';
			$movement_item_record['movement_date'] = (isset($movement_record['date'])) ? $movement_record['date'] : '';
			$movement_item_record['movement_status'] = (isset($movement_record['status'])) ? $movement_record['status'] : '';

			$movement_item_record['location_type'] = (isset($movement_record['location_type'])) ? $movement_record['location_type'] : '';
			$movement_item_record['location_number'] = (isset($movement_record['location_number'])) ? $movement_record['location_number'] : '';
			$movement_item_record['location_name'] = (isset($movement_record['location_name'])) ? $movement_record['location_name'] : '';
			$movement_item_record['location_date'] = (isset($movement_record['location_date'])) ? $movement_record['location_date'] : '';
			$movement_item_record['location_status'] = (isset($movement_record['location_status'])) ? $movement_record['location_status'] : '';

			$movement_item_record['location_to_type'] = (isset($movement_record['location_to_type'])) ? $movement_record['location_to_type'] : '';
			$movement_item_record['location_to_number'] = (isset($movement_record['location_to_number'])) ? $movement_record['location_to_number'] : '';
			$movement_item_record['location_to_name'] = (isset($movement_record['location_to_name'])) ? $movement_record['location_to_name'] : '';
			$movement_item_record['location_to_date'] = (isset($movement_record['location_to_date'])) ? $movement_record['location_to_date'] : '';
			$movement_item_record['location_to_status'] = (isset($movement_record['location_to_status'])) ? $movement_record['location_to_status'] : '';

			$movement_item_record['product_type'] = (isset($arr_product_lookup[$movement_item->product_id])) ? $arr_product_lookup[$movement_item->product_id]->type : '';
			$movement_item_record['product_number'] = (isset($arr_product_lookup[$movement_item->product_id])) ? $arr_product_lookup[$movement_item->product_id]->number : '';
			$movement_item_record['product_name'] = (isset($arr_product_lookup[$movement_item->product_id])) ? $arr_product_lookup[$movement_item->product_id]->name : '';
			$movement_item_record['product_date'] = (isset($arr_product_lookup[$movement_item->product_id])) ? $arr_product_lookup[$movement_item->product_id]->date : '';
			$movement_item_record['product_status'] = (isset($arr_product_lookup[$movement_item->product_id])) ? $arr_product_lookup[$movement_item->product_id]->status : '';

			$this->core_model->insert('movement_item', $movement_item_record);

			// update inventory
			$this->db->set('quantity', "quantity - ({$movement_item->quantity})", false);
			$this->db->where('product_id', $movement_item->product_id);
			$this->db->where('location_id', $movement_item->location_id);
			$this->core_model->update('inventory', 0);

			$this->db->set('quantity', "quantity + ({$movement_item->quantity})", false);
			$this->db->where('product_id', $movement_item->product_id);
			$this->db->where('location_id', $movement_item->location_to_id);
			$this->core_model->update('inventory', 0);

			// insert stock
			$stock_record = array();
			$stock_record['location_id'] = $movement_item->location_id;
			$stock_record['product_id'] = $movement_item->product_id;
			$stock_record['ref_id'] = $movement_id;
			$stock_record['type'] = 'Movement';
			$stock_record['date'] = $movement_record['date'];
			$stock_record['quantity_out'] = $movement_item->quantity;

			$stock_record['location_type'] = $movement_item_record['location_type'];
			$stock_record['location_number'] = $movement_item_record['location_number'];
			$stock_record['location_name'] = $movement_item_record['location_name'];
			$stock_record['location_date'] = $movement_item_record['location_date'];
			$stock_record['location_status'] = $movement_item_record['location_status'];

			$stock_record['product_type'] = $movement_item_record['product_type'];
			$stock_record['product_number'] = $movement_item_record['product_number'];
			$stock_record['product_name'] = $movement_item_record['product_name'];
			$stock_record['product_date'] = $movement_item_record['product_date'];
			$stock_record['product_status'] = $movement_item_record['product_status'];

			$stock_record['ref_type'] = $movement_item_record['movement_type'];
			$stock_record['ref_number'] = $movement_item_record['movement_number'];
			$stock_record['ref_name'] = $movement_item_record['movement_name'];
			$stock_record['ref_date'] = $movement_item_record['movement_date'];
			$stock_record['ref_status'] = $movement_item_record['movement_status'];
			$this->core_model->insert('stock', $stock_record);

			$stock_record = array();
			$stock_record['location_id'] = $movement_item->location_to_id;
			$stock_record['product_id'] = $movement_item->product_id;
			$stock_record['ref_id'] = $movement_id;
			$stock_record['type'] = 'Movement';
			$stock_record['date'] = $movement_record['date'];
			$stock_record['quantity_in'] = $movement_item->quantity;

			$stock_record['location_type'] = $movement_item_record['location_to_type'];
			$stock_record['location_number'] = $movement_item_record['location_to_number'];
			$stock_record['location_name'] = $movement_item_record['location_to_name'];
			$stock_record['location_date'] = $movement_item_record['location_to_date'];
			$stock_record['location_status'] = $movement_item_record['location_to_status'];

			$stock_record['product_type'] = $movement_item_record['product_type'];
			$stock_record['product_number'] = $movement_item_record['product_number'];
			$stock_record['product_name'] = $movement_item_record['product_name'];
			$stock_record['product_date'] = $movement_item_record['product_date'];
			$stock_record['product_status'] = $movement_item_record['product_status'];

			$stock_record['ref_type'] = $movement_item_record['movement_type'];
			$stock_record['ref_number'] = $movement_item_record['movement_number'];
			$stock_record['ref_name'] = $movement_item_record['movement_name'];
			$stock_record['ref_date'] = $movement_item_record['movement_date'];
			$stock_record['ref_status'] = $movement_item_record['movement_status'];
			$this->core_model->insert('stock', $stock_record);
		}
	}

	private function _delete_movement_item($movement_id)
	{
		$this->db->where('movement_id', $movement_id);
		$arr_old_movement_item = $this->core_model->get('movement_item');

		foreach ($arr_old_movement_item as $old_movement_item)
		{
			// update inventory
			$this->db->set('quantity', "quantity + ({$old_movement_item->quantity})", false);
			$this->db->where('product_id', $old_movement_item->product_id);
			$this->db->where('location_id', $old_movement_item->location_id);
			$this->core_model->update('inventory', 0);

			$this->db->set('quantity', "quantity - ({$old_movement_item->quantity})", false);
			$this->db->where('product_id', $old_movement_item->product_id);
			$this->db->where('location_id', $old_movement_item->location_to_id);
			$this->core_model->update('inventory', 0);

			$this->core_model->delete('movement_item', $old_movement_item->id);
		}

		// delete stock
		$this->db->where('ref_id', $movement_id);
		$this->db->where('type', 'Movement');
		$this->core_model->delete('stock');
	}

	private function _get_location()
	{
		$this->db->order_by('name');
		return $this->core_model->get('location');
	}

	private function _get_product()
	{
		$this->db->where('status', 'Active');
		$this->db->where('type', 'Product');
		$this->db->order_by('name');
		return $this->core_model->get('product');
	}

	private function _update_movement_item($movement_id, $movement_record, $arr_movement_item)
	{
		$this->_delete_movement_item($movement_id);
		$this->_add_movement_item($movement_id, $movement_record, $arr_movement_item);
	}

	private function _validate_add($movement_record)
	{
		$this->db->where('number', $movement_record['number']);
		$count_movement = $this->core_model->count('movement');

		if ($count_movement > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($movement_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $movement_id);
		$count_movement = $this->core_model->count('movement');

		if ($count_movement > 0)
		{
			throw new Exception('Data cannot be deleted');
		}
	}

	private function _validate_edit($movement_id, $movement_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $movement_id);
		$count_movement = $this->core_model->count('movement');

		if ($count_movement > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $movement_id);
		$this->db->where('number', $movement_record['number']);
		$count_movement = $this->core_model->count('movement');

		if ($count_movement > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}