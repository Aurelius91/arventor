<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Receive extends CI_Controller
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

		if (!isset($acl['receive']) || $acl['receive']->add <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Receive';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('receive_add', $arr_data);
	}

	public function edit($receive_id = 0)
	{
		$acl = $this->_acl;

		if ($receive_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['receive']) || $acl['receive']->edit <= 0)
		{
			redirect(base_url());
		}

		$receive = $this->core_model->get('receive', $receive_id);
		$receive->date_display = date('Y-m-d', $receive->date);

		$this->db->where('receive_id', $receive->id);
		$arr_receive_item = $this->core_model->get('receive_item');

		foreach ($arr_receive_item as $receive_item)
		{
			$receive_item->quantity_display = number_format($receive_item->quantity, 0, '', '');
		}

		$receive->arr_receive_item = $arr_receive_item;

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Receive';
		$arr_data['receive'] = $receive;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('receive_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['receive']) || $acl['receive']->list <= 0)
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
		$arr_receive = $this->core_model->get('receive');

		foreach ($arr_receive as $receive)
		{
			$receive->date = date('d F Y', $receive->date);
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

		$count_receive = $this->core_model->count('receive');
		$count_page = ceil($count_receive / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Receive';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_receive'] = $arr_receive;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('receive', $arr_data);
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

			if (!isset($acl['receive']) || $acl['receive']->add <= 0)
			{
				throw new Exception('You have no access to add receive. Please contact your administrator.');
			}

			$receive_record = array();
			$arr_receive_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'receive_item_receive_item')
				{
					$arr_receive_item = json_decode($v);
				}
				else
				{
					$receive_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$receive_record = $this->cms_function->populate_foreign_field($receive_record['location_id'], $receive_record, 'location');

			$this->_validate_add($receive_record);

			$receive_id = $this->core_model->insert('receive', $receive_record);
			$receive_record['id'] = $receive_id;
			$receive_record['last_query'] = $this->db->last_query();

			if (!isset($receive_record['number']) || (isset($receive_record['number']) && $receive_record['number'] == ''))
			{
				$receive_record['number'] = '#RCV' . str_pad($receive_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('receive', $receive_id, array('number' => $receive_record['number']));
			}

			$receive_record['name'] = $receive_record['number'];

			$this->cms_function->system_log($receive_record['id'], 'add', $receive_record, array(), 'receive');

			$this->_add_receive_item($receive_id, $receive_record, $arr_receive_item);

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('color_id' => $color_id));
			}

			$json['receive_id'] = $receive_id;

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

	public function ajax_change_status($receive_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($receive_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['receive']) || $acl['receive']->edit <= 0)
			{
				throw new Exception('You have no access to edit receive. Please contact your administrator.');
			}

			$old_receive = $this->core_model->get('receive', $receive_id);

			$old_receive_record = array();

			foreach ($old_receive as $key => $value)
			{
				$old_receive_record[$key] = $value;
			}

			$receive_record = array();

			foreach ($_POST as $k => $v)
			{
				$receive_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('receive', $receive_id, $receive_record);
			$receive_record['id'] = $receive_id;
			$receive_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($receive_id, 'status', $receive_record, $old_receive_record, 'receive');

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

	public function ajax_delete($receive_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($receive_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['receive']) || $acl['receive']->delete <= 0)
			{
				throw new Exception('You have no access to delete receive. Please contact your administrator.');
			}

			$receive = $this->core_model->get('receive', $receive_id);
			$updated = $_POST['updated'];
			$receive_record = array();

			foreach ($receive as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$receive_record[$k] = $v;
				}
			}

			$this->_validate_delete($receive_id);

			$this->core_model->delete('receive', $receive_id);
			$receive_record['id'] = $receive->id;
			$receive_record['last_query'] = $this->db->last_query();
			$receive_record['name'] = $receive_record['number'];

			$this->cms_function->system_log($receive_record['id'], 'delete', $receive_record, array(), 'receive');

			if ($this->_has_image > 0)
			{
				$this->db->where('receive_id', $receive_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->_delete_receive_item($receive_id);

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

	public function ajax_edit($receive_id)
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

			if (!isset($acl['receive']) || $acl['receive']->edit <= 0)
			{
				throw new Exception('You have no access to edit receive. Please contact your administrator.');
			}

			$old_receive = $this->core_model->get('receive', $receive_id);

			$old_receive_record = array();

			foreach ($old_receive as $key => $value)
			{
				$old_receive_record[$key] = $value;
			}

			$receive_record = array();
			$arr_receive_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'receive_item_receive_item')
				{
					$arr_receive_item = json_decode($v);
				}
				else
				{
					$receive_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($receive_id, $receive_record);

			$this->core_model->update('receive', $receive_id, $receive_record);
			$receive_record['id'] = $receive_id;
			$receive_record['last_query'] = $this->db->last_query();
			$receive_record['name'] = $receive_record['number'];

			$this->cms_function->system_log($receive_record['id'], 'edit', $receive_record, $old_receive_record, 'receive');

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

            $this->_update_receive_item($receive_id, $receive_record, $arr_receive_item);

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

	public function ajax_get($receive_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($receive_id <= 0)
			{
				throw new Exception();
			}

			$receive = $this->core_model->get('receive', $receive_id);

			$json['receive'] = $receive;
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




	private function _add_receive_item($receive_id, $receive_record, $arr_receive_item)
	{
		// get product
		$arr_product = $this->core_model->get('product');
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_receive_item as $receive_item)
		{
			$receive_item_record = array();
			$receive_item_record['receive_id'] = $receive_id;
			$receive_item_record['location_id'] = $receive_item->location_id;
			$receive_item_record['product_id'] = $receive_item->product_id;

			$receive_item_record['quantity'] = $receive_item->quantity;

			$receive_item_record['receive_type'] = (isset($receive_record['type'])) ? $receive_record['type'] : '';
			$receive_item_record['receive_number'] = (isset($receive_record['number'])) ? $receive_record['number'] : '';
			$receive_item_record['receive_name'] = '';
			$receive_item_record['receive_date'] = (isset($receive_record['date'])) ? $receive_record['date'] : '';
			$receive_item_record['receive_status'] = (isset($receive_record['status'])) ? $receive_record['status'] : '';

			$receive_item_record['location_type'] = (isset($receive_record['location_type'])) ? $receive_record['location_type'] : '';
			$receive_item_record['location_number'] = (isset($receive_record['location_number'])) ? $receive_record['location_number'] : '';
			$receive_item_record['location_name'] = (isset($receive_record['location_name'])) ? $receive_record['location_name'] : '';
			$receive_item_record['location_date'] = (isset($receive_record['location_date'])) ? $receive_record['location_date'] : '';
			$receive_item_record['location_status'] = (isset($receive_record['location_status'])) ? $receive_record['location_status'] : '';

			$receive_item_record['product_type'] = (isset($arr_product_lookup[$receive_item->product_id])) ? $arr_product_lookup[$receive_item->product_id]->type : '';
			$receive_item_record['product_number'] = (isset($arr_product_lookup[$receive_item->product_id])) ? $arr_product_lookup[$receive_item->product_id]->number : '';
			$receive_item_record['product_name'] = (isset($arr_product_lookup[$receive_item->product_id])) ? $arr_product_lookup[$receive_item->product_id]->name : '';
			$receive_item_record['product_date'] = (isset($arr_product_lookup[$receive_item->product_id])) ? $arr_product_lookup[$receive_item->product_id]->date : '';
			$receive_item_record['product_status'] = (isset($arr_product_lookup[$receive_item->product_id])) ? $arr_product_lookup[$receive_item->product_id]->status : '';

			$this->core_model->insert('receive_item', $receive_item_record);

			// update inventory
			$this->db->set('quantity', "quantity + ({$receive_item->quantity})", false);
			$this->db->where('product_id', $receive_item->product_id);
			$this->db->where('location_id', $receive_item->location_id);
			$this->core_model->update('inventory', 0);

			// insert stock
			$stock_record = array();
			$stock_record['location_id'] = $receive_item->location_id;
			$stock_record['product_id'] = $receive_item->product_id;
			$stock_record['ref_id'] = $receive_id;
			$stock_record['type'] = 'Receive';
			$stock_record['date'] = $receive_record['date'];

			$stock_record['quantity_in'] = $receive_item->quantity;

			$stock_record['location_type'] = $receive_item_record['location_type'];
			$stock_record['location_number'] = $receive_item_record['location_number'];
			$stock_record['location_name'] = $receive_item_record['location_name'];
			$stock_record['location_date'] = $receive_item_record['location_date'];
			$stock_record['location_status'] = $receive_item_record['location_status'];

			$stock_record['product_type'] = $receive_item_record['product_type'];
			$stock_record['product_number'] = $receive_item_record['product_number'];
			$stock_record['product_name'] = $receive_item_record['product_name'];
			$stock_record['product_date'] = $receive_item_record['product_date'];
			$stock_record['product_status'] = $receive_item_record['product_status'];

			$stock_record['ref_type'] = $receive_item_record['receive_type'];
			$stock_record['ref_number'] = $receive_item_record['receive_number'];
			$stock_record['ref_name'] = $receive_item_record['receive_name'];
			$stock_record['ref_date'] = $receive_item_record['receive_date'];
			$stock_record['ref_status'] = $receive_item_record['receive_status'];
			$this->core_model->insert('stock', $stock_record);
		}
	}

	private function _delete_receive_item($receive_id)
	{
		$this->db->where('receive_id', $receive_id);
		$arr_old_receive_item = $this->core_model->get('receive_item');

		foreach ($arr_old_receive_item as $old_receive_item)
		{
			// update inventory
			$this->db->set('quantity', "quantity - ({$old_receive_item->quantity})", false);
			$this->db->where('product_id', $old_receive_item->product_id);
			$this->db->where('location_id', $old_receive_item->location_id);
			$this->core_model->update('inventory', 0);

			$this->core_model->delete('receive_item', $old_receive_item->id);
		}

		// delete stock
		$this->db->where('ref_id', $receive_id);
		$this->db->where('type', 'Receive');
		$this->core_model->delete('stock');
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
		$this->db->where('type', 'Product');
		$this->db->order_by('name');
		return $this->core_model->get('product');
	}

	private function _update_receive_item($receive_id, $receive_record, $arr_receive_item)
	{
		$this->_delete_receive_item($receive_id);
		$this->_add_receive_item($receive_id, $receive_record, $arr_receive_item);
	}

	private function _validate_add($receive_record)
	{
		$this->db->where('number', $receive_record['number']);
		$count_receive = $this->core_model->count('receive');

		if ($count_receive > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($receive_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $receive_id);
		$count_receive = $this->core_model->count('receive');

		if ($count_receive > 0)
		{
			throw new Exception('Data cannot be deleted');
		}
	}

	private function _validate_edit($receive_id, $receive_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $receive_id);
		$count_receive = $this->core_model->count('receive');

		if ($count_receive > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $receive_id);
		$this->db->where('number', $receive_record['number']);
		$count_receive = $this->core_model->count('receive');

		if ($count_receive > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}