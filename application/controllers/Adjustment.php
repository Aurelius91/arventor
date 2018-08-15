<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Adjustment extends CI_Controller
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

		if (!isset($acl['adjustment']) || $acl['adjustment']->add <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'adjustment';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('adjustment_add', $arr_data);
	}

	public function edit($adjustment_id = 0)
	{
		$acl = $this->_acl;

		if ($adjustment_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['adjustment']) || $acl['adjustment']->edit <= 0)
		{
			redirect(base_url());
		}

		$adjustment = $this->core_model->get('adjustment', $adjustment_id);
		$adjustment->date_display = date('Y-m-d', $adjustment->date);

		$this->db->where('adjustment_id', $adjustment->id);
		$arr_adjustment_item = $this->core_model->get('adjustment_item');

		foreach ($arr_adjustment_item as $adjustment_item)
		{
			$adjustment_item->quantity_display = number_format($adjustment_item->quantity, 0, '', '');
		}

		$adjustment->arr_adjustment_item = $arr_adjustment_item;

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'adjustment';
		$arr_data['adjustment'] = $adjustment;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();

		$this->load->view('html', $arr_data);
		$this->load->view('adjustment_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['adjustment']) || $acl['adjustment']->list <= 0)
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
		$arr_adjustment = $this->core_model->get('adjustment');

		foreach ($arr_adjustment as $adjustment)
		{
			$adjustment->date = date('d F Y', $adjustment->date);
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

		$count_adjustment = $this->core_model->count('adjustment');
		$count_page = ceil($count_adjustment / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'adjustment';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_adjustment'] = $arr_adjustment;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('adjustment', $arr_data);
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

			if (!isset($acl['adjustment']) || $acl['adjustment']->add <= 0)
			{
				throw new Exception('You have no access to add adjustment. Please contact your administrator.');
			}

			$adjustment_record = array();
			$arr_adjustment_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'adjustment_item_adjustment_item')
				{
					$arr_adjustment_item = json_decode($v);
				}
				else
				{
					$adjustment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$adjustment_record = $this->cms_function->populate_foreign_field($adjustment_record['location_id'], $adjustment_record, 'location');

			$this->_validate_add($adjustment_record);

			$adjustment_id = $this->core_model->insert('adjustment', $adjustment_record);
			$adjustment_record['id'] = $adjustment_id;
			$adjustment_record['last_query'] = $this->db->last_query();

			if (!isset($adjustment_record['number']) || (isset($adjustment_record['number']) && $adjustment_record['number'] == ''))
			{
				$adjustment_record['number'] = '#ADJ' . str_pad($adjustment_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('adjustment', $adjustment_id, array('number' => $adjustment_record['number']));
			}

			$adjustment_record['name'] = $adjustment_record['number'];

			$this->cms_function->system_log($adjustment_record['id'], 'add', $adjustment_record, array(), 'adjustment');

			$this->_add_adjustment_item($adjustment_id, $adjustment_record, $arr_adjustment_item);

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('color_id' => $color_id));
			}

			$json['adjustment_id'] = $adjustment_id;

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

	public function ajax_change_status($adjustment_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($adjustment_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['adjustment']) || $acl['adjustment']->edit <= 0)
			{
				throw new Exception('You have no access to edit adjustment. Please contact your administrator.');
			}

			$old_adjustment = $this->core_model->get('adjustment', $adjustment_id);

			$old_adjustment_record = array();

			foreach ($old_adjustment as $key => $value)
			{
				$old_adjustment_record[$key] = $value;
			}

			$adjustment_record = array();

			foreach ($_POST as $k => $v)
			{
				$adjustment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('adjustment', $adjustment_id, $adjustment_record);
			$adjustment_record['id'] = $adjustment_id;
			$adjustment_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($adjustment_id, 'status', $adjustment_record, $old_adjustment_record, 'adjustment');

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

	public function ajax_delete($adjustment_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($adjustment_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['adjustment']) || $acl['adjustment']->delete <= 0)
			{
				throw new Exception('You have no access to delete adjustment. Please contact your administrator.');
			}

			$adjustment = $this->core_model->get('adjustment', $adjustment_id);
			$updated = $_POST['updated'];
			$adjustment_record = array();

			foreach ($adjustment as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$adjustment_record[$k] = $v;
				}
			}

			$this->_validate_delete($adjustment_id);

			$this->core_model->delete('adjustment', $adjustment_id);
			$adjustment_record['id'] = $adjustment->id;
			$adjustment_record['last_query'] = $this->db->last_query();
			$adjustment_record['name'] = $adjustment_record['number'];

			$this->cms_function->system_log($adjustment_record['id'], 'delete', $adjustment_record, array(), 'adjustment');

			if ($this->_has_image > 0)
			{
				$this->db->where('adjustment_id', $adjustment_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->_delete_adjustment_item($adjustment_id);

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

	public function ajax_edit($adjustment_id)
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

			if (!isset($acl['adjustment']) || $acl['adjustment']->edit <= 0)
			{
				throw new Exception('You have no access to edit adjustment. Please contact your administrator.');
			}

			$old_adjustment = $this->core_model->get('adjustment', $adjustment_id);

			$old_adjustment_record = array();

			foreach ($old_adjustment as $key => $value)
			{
				$old_adjustment_record[$key] = $value;
			}

			$adjustment_record = array();
			$arr_adjustment_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'adjustment_item_adjustment_item')
				{
					$arr_adjustment_item = json_decode($v);
				}
				else
				{
					$adjustment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($adjustment_id, $adjustment_record);

			$this->core_model->update('adjustment', $adjustment_id, $adjustment_record);
			$adjustment_record['id'] = $adjustment_id;
			$adjustment_record['last_query'] = $this->db->last_query();
			$adjustment_record['name'] = $adjustment_record['number'];

			$this->cms_function->system_log($adjustment_record['id'], 'edit', $adjustment_record, $old_adjustment_record, 'adjustment');

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

            $this->_update_adjustment_item($adjustment_id, $adjustment_record, $arr_adjustment_item);

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

	public function ajax_get($adjustment_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($adjustment_id <= 0)
			{
				throw new Exception();
			}

			$adjustment = $this->core_model->get('adjustment', $adjustment_id);

			$json['adjustment'] = $adjustment;
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




	private function _add_adjustment_item($adjustment_id, $adjustment_record, $arr_adjustment_item)
	{
		// get product
		$arr_product = $this->core_model->get('product');
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_adjustment_item as $adjustment_item)
		{
			$adjustment_item_record = array();
			$adjustment_item_record['adjustment_id'] = $adjustment_id;
			$adjustment_item_record['location_id'] = $adjustment_item->location_id;
			$adjustment_item_record['product_id'] = $adjustment_item->product_id;

			$adjustment_item_record['quantity'] = $adjustment_item->quantity;

			$adjustment_item_record['adjustment_type'] = (isset($adjustment_record['type'])) ? $adjustment_record['type'] : '';
			$adjustment_item_record['adjustment_number'] = (isset($adjustment_record['number'])) ? $adjustment_record['number'] : '';
			$adjustment_item_record['adjustment_name'] = '';
			$adjustment_item_record['adjustment_date'] = (isset($adjustment_record['date'])) ? $adjustment_record['date'] : '';
			$adjustment_item_record['adjustment_status'] = (isset($adjustment_record['status'])) ? $adjustment_record['status'] : '';

			$adjustment_item_record['location_type'] = (isset($adjustment_record['location_type'])) ? $adjustment_record['location_type'] : '';
			$adjustment_item_record['location_number'] = (isset($adjustment_record['location_number'])) ? $adjustment_record['location_number'] : '';
			$adjustment_item_record['location_name'] = (isset($adjustment_record['location_name'])) ? $adjustment_record['location_name'] : '';
			$adjustment_item_record['location_date'] = (isset($adjustment_record['location_date'])) ? $adjustment_record['location_date'] : '';
			$adjustment_item_record['location_status'] = (isset($adjustment_record['location_status'])) ? $adjustment_record['location_status'] : '';

			$adjustment_item_record['product_type'] = (isset($arr_product_lookup[$adjustment_item->product_id])) ? $arr_product_lookup[$adjustment_item->product_id]->type : '';
			$adjustment_item_record['product_number'] = (isset($arr_product_lookup[$adjustment_item->product_id])) ? $arr_product_lookup[$adjustment_item->product_id]->number : '';
			$adjustment_item_record['product_name'] = (isset($arr_product_lookup[$adjustment_item->product_id])) ? $arr_product_lookup[$adjustment_item->product_id]->name : '';
			$adjustment_item_record['product_date'] = (isset($arr_product_lookup[$adjustment_item->product_id])) ? $arr_product_lookup[$adjustment_item->product_id]->date : '';
			$adjustment_item_record['product_status'] = (isset($arr_product_lookup[$adjustment_item->product_id])) ? $arr_product_lookup[$adjustment_item->product_id]->status : '';

			$this->core_model->insert('adjustment_item', $adjustment_item_record);

			// update inventory
			$this->db->set('quantity', "quantity + ({$adjustment_item->quantity})", false);
			$this->db->where('product_id', $adjustment_item->product_id);
			$this->db->where('location_id', $adjustment_item->location_id);
			$this->core_model->update('inventory', 0);

			// insert stock
			$stock_record = array();
			$stock_record['location_id'] = $adjustment_item->location_id;
			$stock_record['product_id'] = $adjustment_item->product_id;
			$stock_record['ref_id'] = $adjustment_id;
			$stock_record['type'] = 'Adjustment';
			$stock_record['date'] = $adjustment_record['date'];

			if (($adjustment_item->quantity) > 0)
			{
				$stock_record['quantity_in'] = $adjustment_item->quantity;
			}
			else
			{
				$stock_record['quantity_out'] = $adjustment_item->quantity * -1;
			}

			$stock_record['location_type'] = $adjustment_item_record['location_type'];
			$stock_record['location_number'] = $adjustment_item_record['location_number'];
			$stock_record['location_name'] = $adjustment_item_record['location_name'];
			$stock_record['location_date'] = $adjustment_item_record['location_date'];
			$stock_record['location_status'] = $adjustment_item_record['location_status'];

			$stock_record['product_type'] = $adjustment_item_record['product_type'];
			$stock_record['product_number'] = $adjustment_item_record['product_number'];
			$stock_record['product_name'] = $adjustment_item_record['product_name'];
			$stock_record['product_date'] = $adjustment_item_record['product_date'];
			$stock_record['product_status'] = $adjustment_item_record['product_status'];

			$stock_record['ref_type'] = $adjustment_item_record['adjustment_type'];
			$stock_record['ref_number'] = $adjustment_item_record['adjustment_number'];
			$stock_record['ref_name'] = $adjustment_item_record['adjustment_name'];
			$stock_record['ref_date'] = $adjustment_item_record['adjustment_date'];
			$stock_record['ref_status'] = $adjustment_item_record['adjustment_status'];
			$this->core_model->insert('stock', $stock_record);
		}
	}

	private function _delete_adjustment_item($adjustment_id)
	{
		$this->db->where('adjustment_id', $adjustment_id);
		$arr_old_adjustment_item = $this->core_model->get('adjustment_item');

		foreach ($arr_old_adjustment_item as $old_adjustment_item)
		{
			// update inventory
			$this->db->set('quantity', "quantity - ({$old_adjustment_item->quantity})", false);
			$this->db->where('product_id', $old_adjustment_item->product_id);
			$this->db->where('location_id', $old_adjustment_item->location_id);
			$this->core_model->update('inventory', 0);

			$this->core_model->delete('adjustment_item', $old_adjustment_item->id);
		}

		// delete stock
		$this->db->where('ref_id', $adjustment_id);
		$this->db->where('type', 'Adjustment');
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

	private function _update_adjustment_item($adjustment_id, $adjustment_record, $arr_adjustment_item)
	{
		$this->_delete_adjustment_item($adjustment_id);
		$this->_add_adjustment_item($adjustment_id, $adjustment_record, $arr_adjustment_item);
	}

	private function _validate_add($adjustment_record)
	{
		$this->db->where('number', $adjustment_record['number']);
		$count_adjustment = $this->core_model->count('adjustment');

		if ($count_adjustment > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($adjustment_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $adjustment_id);
		$count_adjustment = $this->core_model->count('adjustment');

		if ($count_adjustment > 0)
		{
			throw new Exception('Data cannot be deleted');
		}
	}

	private function _validate_edit($adjustment_id, $adjustment_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $adjustment_id);
		$count_adjustment = $this->core_model->count('adjustment');

		if ($count_adjustment > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $adjustment_id);
		$this->db->where('number', $adjustment_record['number']);
		$count_adjustment = $this->core_model->count('adjustment');

		if ($count_adjustment > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}