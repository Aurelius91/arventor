<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends CI_Controller
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

		if (!isset($acl['product']) || $acl['product']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Product';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_brand'] = $this->_get_brand();
		$arr_data['arr_category'] = $this->_get_category();

		$this->load->view('html', $arr_data);
		$this->load->view('product_add', $arr_data);
	}

	public function edit($product_id = 0)
	{
		$acl = $this->_acl;

		if ($product_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['product']) || $acl['product']->edit <= 0)
		{
			redirect(base_url());
		}

		$product = $this->core_model->get('product', $product_id);
		$product->price_display = number_format($product->price, 0, '', '');
		$product->weight_display = number_format($product->weight, 0, '', '');

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Product';
		$arr_data['product'] = $product;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_brand'] = $this->_get_brand();
		$arr_data['arr_category'] = $this->_get_category();

		$this->load->view('html', $arr_data);
		$this->load->view('product_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['product']) || $acl['product']->list <= 0)
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
		$this->db->order_by("number");
		$arr_product = $this->core_model->get('product');

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

		$count_product = $this->core_model->count('product');
		$count_page = ceil($count_product / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Product';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_product'] = $arr_product;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('product', $arr_data);
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

			if (!isset($acl['product']) || $acl['product']->add <= 0)
			{
				throw new Exception('You have no access to add product. Please contact your administrator.');
			}

			$product_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$product_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$product_record = $this->cms_function->populate_foreign_field($product_record['brand_id'], $product_record, 'brand');
			$product_record = $this->cms_function->populate_foreign_field($product_record['category_id'], $product_record, 'category');

			$product_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($product_record['name']));
            $product_record['url_name'] = preg_replace("/[\s_]/", "-", $product_record['url_name']);

			$this->_validate_add($product_record);

			$product_id = $this->core_model->insert('product', $product_record);
			$product_record['id'] = $product_id;
			$product_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($product_record['id'], 'add', $product_record, array(), 'product');

			if (!isset($product_record['number']) || (isset($product_record['number']) && $product_record['number'] == ''))
			{
				$product_record['number'] = '#P' . str_pad($product_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('product', $product_id, array('number' => $product_record['number']));
			}

			if (!isset($product_record['barcode']) || (isset($product_record['barcode']) && $product_record['barcode'] == ''))
			{
				$product_record['barcode'] = date('ymd', time()) . '' . str_pad($product_id, 4, 0, STR_PAD_LEFT);
				$this->core_model->update('product', $product_id, array('barcode' => $product_record['barcode']));
			}

			if ($product_record['type'] == 'Product')
			{
				$this->_add_inventory($product_id, $product_record);
			}

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('color_id' => $color_id));
			}

			$json['product_id'] = $product_id;

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

	public function ajax_change_status($product_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($product_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['product']) || $acl['product']->edit <= 0)
			{
				throw new Exception('You have no access to edit product. Please contact your administrator.');
			}

			$old_product = $this->core_model->get('product', $product_id);

			$old_product_record = array();

			foreach ($old_product as $key => $value)
			{
				$old_product_record[$key] = $value;
			}

			$product_record = array();

			foreach ($_POST as $k => $v)
			{
				$product_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('product', $product_id, $product_record);
			$product_record['id'] = $product_id;
			$product_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($product_id, 'status', $product_record, $old_product_record, 'product');

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

	public function ajax_delete($product_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($product_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['product']) || $acl['product']->delete <= 0)
			{
				throw new Exception('You have no access to delete product. Please contact your administrator.');
			}

			$product = $this->core_model->get('product', $product_id);
			$updated = $_POST['updated'];
			$product_record = array();

			foreach ($product as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$product_record[$k] = $v;
				}
			}

			$this->_validate_delete($product_id);

			$this->core_model->delete('product', $product_id);
			$product_record['id'] = $product->id;
			$product_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($product_record['id'], 'delete', $product_record, array(), 'product');

			$this->_delete_inventory($product_id);

			if ($this->_has_image > 0)
			{
				$this->db->where('product_id', $product_id);
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

	public function ajax_edit($product_id)
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

			if (!isset($acl['product']) || $acl['product']->edit <= 0)
			{
				throw new Exception('You have no access to edit product. Please contact your administrator.');
			}

			$old_product = $this->core_model->get('product', $product_id);

			$old_product_record = array();

			foreach ($old_product as $key => $value)
			{
				$old_product_record[$key] = $value;
			}

			$product_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$product_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$product_record['type'] = $old_product_record['type'];

			$product_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($product_record['name']));
            $product_record['url_name'] = preg_replace("/[\s_]/", "-", $product_record['url_name']);

			$this->_validate_edit($product_id, $product_record);

			$this->core_model->update('product', $product_id, $product_record);
			$product_record['id'] = $product_id;
			$product_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($product_record['id'], 'edit', $product_record, $old_product_record, 'product');
			$this->cms_function->update_foreign_field(array('adjustment_item', 'inventory', 'movement_item', 'purchase_item', 'receive_item', 'sale_item', 'stock'), $product_record, 'product');

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

	public function ajax_get($product_id = 0, $barcode = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($product_id <= 0 && $barcode == '')
			{
				throw new Exception();
			}

			$product = null;

			if ($product_id > 0)
			{
				$product = $this->core_model->get('product', $product_id);
			}
			else
			{
				$this->db->where('barcode', $barcode);
				$arr_product = $this->core_model->get('product');

				if (count($arr_product) <= 0)
				{
					throw new Exception('No Product Found');
				}

				$product = $arr_product[0];
				$product->price_display = number_format($product->price, 0, ',', '.');
			}

			$json['product'] = $product;
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

	public function ajax_search($query = '')
	{
		$json['success'] = true;

		try
		{
			$query = urldecode($query);

			$this->db->like('name', $query);
			$this->db->or_like('barcode', $query);
			$arr_product = $this->core_model->get('product');

			$arr_results = array();

			foreach ($arr_product as $product)
			{
				$result = new stdClass();
				$result->name = $product->name;
				$result->value = $product->id;
				$result->text = '';
				$result->disabled = false;

				$arr_results[] = clone $result;
			}

			$json['results'] = $arr_results;
		}
		catch (Exception $e)
		{
			$json['message'] = $e->getMessage();
			$json['success'] = false;

			if ($json['message'] == '')
			{
				$json['message'] = 'Server error.';
			}
		}

		echo json_encode($json);
	}




	private function _add_inventory($product_id, $product_record)
	{
		// get all location
		$arr_location = $this->core_model->get('location');

		foreach ($arr_location as $location)
		{
			$inventory_record = array();

			$inventory_record['location_id'] = $location->id;
			$inventory_record['product_id'] = $product_id;

			$inventory_record['location_type'] = $location->type;
			$inventory_record['location_number'] = $location->number;
			$inventory_record['location_name'] = $location->name;
			$inventory_record['location_date'] = $location->date;
			$inventory_record['location_status'] = $location->status;

			$inventory_record['product_type'] = (isset($product_record['type'])) ? $product_record['type'] : '';
			$inventory_record['product_number'] = (isset($product_record['number'])) ? $product_record['number'] : '';
			$inventory_record['product_name'] = (isset($product_record['name'])) ? $product_record['name'] : '';
			$inventory_record['product_date'] = (isset($product_record['date'])) ? $product_record['date'] : '';
			$inventory_record['product_status'] = (isset($product_record['status'])) ? $product_record['status'] : '';

			$this->core_model->insert('inventory', $inventory_record);
		}
	}

	private function _delete_inventory($product_id)
	{
		$this->db->where('product_id', $product_id);
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

		$this->db->where('product_id', $product_id);
		$this->core_model->delete('inventory');
	}

	private function _get_brand()
	{
		$this->db->order_by('name');
		return $this->core_model->get('brand');
	}

	private function _get_category()
	{
		$this->db->order_by('name');
		return $this->core_model->get('category');
	}

	private function _validate_add($product_record)
	{
		$this->db->where('name', $product_record['name']);
		$count_product = $this->core_model->count('product');

		if ($count_product > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($product_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $product_id);
		$count_product = $this->core_model->count('product');

		if ($count_product > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count adjustment
		$this->db->where('product_id', $product_id);
		$count_adjustment_item = $this->core_model->count('adjustment_item');

		if ($count_adjustment_item > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count movement
		$this->db->where('product_id', $product_id);
		$count_movement_item = $this->core_model->count('movement_item');

		if ($count_movement_item > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count purchase
		$this->db->where('product_id', $product_id);
		$count_purchase_item = $this->core_model->count('purchase_item');

		if ($count_purchase_item > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		// count sale
		$this->db->where('product_id', $product_id);
		$count_sale_item = $this->core_model->count('sale_item');

		if ($count_sale_item > 0)
		{
			throw new Exception('Data cannot be deleted');
		}
	}

	private function _validate_edit($product_id, $product_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $product_id);
		$count_product = $this->core_model->count('product');

		if ($count_product > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $product_id);
		$this->db->where('name', $product_record['name']);
		$count_product = $this->core_model->count('product');

		if ($count_product > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}