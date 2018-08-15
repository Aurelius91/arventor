<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Purchase extends CI_Controller
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

		if (!isset($acl['purchase']) || $acl['purchase']->add <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Purchase';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_vendor'] = $this->_get_vendor();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('purchase_add', $arr_data);
	}

	public function edit($purchase_id = 0)
	{
		$acl = $this->_acl;

		if ($purchase_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['purchase']) || $acl['purchase']->edit <= 0)
		{
			redirect(base_url());
		}

		$purchase = $this->core_model->get('purchase', $purchase_id);
		$purchase->date_display = date('Y-m-d', $purchase->date);
		$purchase->discount_display = number_format($purchase->discount, 0, '', '');
		$purchase->tax_display = number_format($purchase->tax, 0, '', '');
		$purchase->shipping_display = number_format($purchase->shipping, 0, '', '');

		$this->db->where('purchase_id', $purchase->id);
		$arr_purchase_item = $this->core_model->get('purchase_item');

		foreach ($arr_purchase_item as $purchase_item)
		{
			$purchase_item->quantity_display = number_format($purchase_item->quantity, 0, '', '');
			$purchase_item->price_display = number_format($purchase_item->price, 0, '', '');
		}

		$purchase->arr_purchase_item = $arr_purchase_item;

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Purchase';
		$arr_data['purchase'] = $purchase;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_vendor'] = $this->_get_vendor();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('purchase_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['purchase']) || $acl['purchase']->list <= 0)
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
		$arr_purchase = $this->core_model->get('purchase');

		foreach ($arr_purchase as $purchase)
		{
			$purchase->date = date('d F Y', $purchase->date);
			$purchase->total = number_format($purchase->total, 0, ',', '.');
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

		$count_purchase = $this->core_model->count('purchase');
		$count_page = ceil($count_purchase / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Purchase';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_purchase'] = $arr_purchase;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('purchase', $arr_data);
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

			if (!isset($acl['purchase']) || $acl['purchase']->add <= 0)
			{
				throw new Exception('You have no access to add purchase. Please contact your administrator.');
			}

			$purchase_record = array();
			$arr_purchase_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'purchase_item_purchase_item')
				{
					$arr_purchase_item = json_decode($v);
				}
				else
				{
					$purchase_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['location_id'], $purchase_record, 'location');
			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['statement_id'], $purchase_record, 'statement');
			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['vendor_id'], $purchase_record, 'vendor');

			$purchase_record['status'] = 'Pending';

			if ($purchase_record['draft'] <= 0)
			{
				$purchase_record['status'] = ($purchase_record['type'] == 'Cash') ? 'Paid' : $purchase_record['status'];
			}

			$this->_validate_add($purchase_record);

			$purchase_id = $this->core_model->insert('purchase', $purchase_record);
			$purchase_record['id'] = $purchase_id;
			$purchase_record['last_query'] = $this->db->last_query();

			if (!isset($purchase_record['number']) || (isset($purchase_record['number']) && $purchase_record['number'] == ''))
			{
				$purchase_record['number'] = '#P' . str_pad($purchase_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('purchase', $purchase_id, array('number' => $purchase_record['number']));
			}

			$purchase_record['name'] = $purchase_record['number'];

			$this->cms_function->system_log($purchase_record['id'], 'add', $purchase_record, array(), 'purchase');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('purchase_id' => $purchase_id));
			}

			$this->_add_purchase_item($purchase_id, $purchase_record, $arr_purchase_item);

			$json['purchase_id'] = $purchase_id;

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

	public function ajax_change_status($purchase_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($purchase_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['purchase']) || $acl['purchase']->edit <= 0)
			{
				throw new Exception('You have no access to edit purchase. Please contact your administrator.');
			}

			$old_purchase = $this->core_model->get('purchase', $purchase_id);

			$old_purchase_record = array();

			foreach ($old_purchase as $key => $value)
			{
				$old_purchase_record[$key] = $value;
			}

			$purchase_record = array();

			foreach ($_POST as $k => $v)
			{
				$purchase_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('purchase', $purchase_id, $purchase_record);
			$purchase_record['id'] = $purchase_id;
			$purchase_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($purchase_id, 'status', $purchase_record, $old_purchase_record, 'purchase');

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

	public function ajax_delete($purchase_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($purchase_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['purchase']) || $acl['purchase']->delete <= 0)
			{
				throw new Exception('You have no access to delete purchase. Please contact your administrator.');
			}

			$purchase = $this->core_model->get('purchase', $purchase_id);
			$updated = $_POST['updated'];
			$purchase_record = array();

			foreach ($purchase as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$purchase_record[$k] = $v;
				}
			}

			$this->_validate_delete($purchase_id);

			$this->core_model->delete('purchase', $purchase_id);
			$purchase_record['id'] = $purchase->id;
			$purchase_record['last_query'] = $this->db->last_query();
			$purchase_record['name'] = $purchase_record['number'];

			$this->cms_function->system_log($purchase_record['id'], 'delete', $purchase_record, array(), 'purchase');

			if ($this->_has_image > 0)
			{
				$this->db->where('purchase_id', $purchase_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->_delete_purchase_item($purchase_id);

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

	public function ajax_edit($purchase_id)
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

			if (!isset($acl['purchase']) || $acl['purchase']->edit <= 0)
			{
				throw new Exception('You have no access to edit purchase. Please contact your administrator.');
			}

			$old_purchase = $this->core_model->get('purchase', $purchase_id);

			$old_purchase_record = array();

			foreach ($old_purchase as $key => $value)
			{
				$old_purchase_record[$key] = $value;
			}

			$purchase_record = array();
			$arr_purchase_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'purchase_item_purchase_item')
				{
					$arr_purchase_item = json_decode($v);
				}
				else
				{
					$purchase_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['location_id'], $purchase_record, 'location');
			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['statement_id'], $purchase_record, 'statement');
			$purchase_record = $this->cms_function->populate_foreign_field($purchase_record['vendor_id'], $purchase_record, 'vendor');

			$purchase_record['status'] = 'Pending';

			if ($purchase_record['draft'] <= 0)
			{
				$purchase_record['status'] = ($purchase_record['type'] == 'Cash') ? 'Paid' : $purchase_record['status'];
			}

			$this->_validate_edit($purchase_id, $purchase_record);

			$this->core_model->update('purchase', $purchase_id, $purchase_record);
			$purchase_record['id'] = $purchase_id;
			$purchase_record['last_query'] = $this->db->last_query();
			$purchase_record['name'] = $purchase_record['number'];

			$this->cms_function->system_log($purchase_record['id'], 'edit', $purchase_record, $old_purchase_record, 'purchase');
			$this->cms_function->update_foreign_field(array('transaction'), $purchase_record, 'purchase');

			if ($image_id > 0)
            {
                $this->db->where('purchase_id', $purchase_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('purchase_id' => $purchase_id));
            }

            $this->_update_purchase_item($purchase_id, $purchase_record, $arr_purchase_item);

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

	public function ajax_get($purchase_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($purchase_id <= 0)
			{
				throw new Exception();
			}

			$purchase = $this->core_model->get('purchase', $purchase_id);

			$json['purchase'] = $purchase;
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

	public function ajax_get_product($product_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($product_id <= 0)
			{
				throw new Exception();
			}

			$product = $this->core_model->get('product', $product_id);
			$product->price_display = number_format($product->price, 0, '', '');

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




	private function _add_purchase_item($purchase_id, $purchase_record, $arr_purchase_item)
	{
		// get product
		$arr_product = $this->core_model->get('product');
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_purchase_item as $purchase_item)
		{
			$purchase_item_record = array();
			$purchase_item_record['vendor_id'] = $purchase_record['vendor_id'];
			$purchase_item_record['location_id'] = $purchase_record['location_id'];
			$purchase_item_record['product_id'] = $purchase_item->product_id;
			$purchase_item_record['purchase_id'] = $purchase_id;

			$purchase_item_record['price'] = $purchase_item->price;
			$purchase_item_record['quantity'] = $purchase_item->quantity;
			$purchase_item_record['draft'] = $purchase_record['draft'];

			$purchase_item_record['vendor_type'] = $purchase_record['vendor_type'];
			$purchase_item_record['vendor_number'] = $purchase_record['vendor_number'];
			$purchase_item_record['vendor_name'] = $purchase_record['vendor_name'];
			$purchase_item_record['vendor_date'] = $purchase_record['vendor_date'];
			$purchase_item_record['vendor_status'] = $purchase_record['vendor_status'];

			$purchase_item_record['location_type'] = $purchase_record['location_type'];
			$purchase_item_record['location_number'] = $purchase_record['location_number'];
			$purchase_item_record['location_name'] = $purchase_record['location_name'];
			$purchase_item_record['location_date'] = $purchase_record['location_date'];
			$purchase_item_record['location_status'] = $purchase_record['location_status'];

			$purchase_item_record['product_type'] = (isset($arr_product_lookup[$purchase_item->product_id])) ? $arr_product_lookup[$purchase_item->product_id]->type : '';
			$purchase_item_record['product_number'] = (isset($arr_product_lookup[$purchase_item->product_id])) ? $arr_product_lookup[$purchase_item->product_id]->number : '';
			$purchase_item_record['product_name'] = (isset($arr_product_lookup[$purchase_item->product_id])) ? $arr_product_lookup[$purchase_item->product_id]->name : '';
			$purchase_item_record['product_date'] = (isset($arr_product_lookup[$purchase_item->product_id])) ? $arr_product_lookup[$purchase_item->product_id]->date : '';
			$purchase_item_record['product_status'] = (isset($arr_product_lookup[$purchase_item->product_id])) ? $arr_product_lookup[$purchase_item->product_id]->status : '';

			$purchase_item_record['purchase_type'] = (isset($purchase_record['type'])) ? $purchase_record['type'] : '';
			$purchase_item_record['purchase_number'] = (isset($purchase_record['number'])) ? $purchase_record['number'] : '';
			$purchase_item_record['purchase_name'] = '';
			$purchase_item_record['purchase_date'] = (isset($purchase_record['date'])) ? $purchase_record['date'] : 0;
			$purchase_item_record['purchase_status'] = (isset($purchase_record['status'])) ? $purchase_record['status'] : '';
			$this->core_model->insert('purchase_item', $purchase_item_record);

			if ($purchase_record['draft'] <= 0 && $arr_product_lookup[$purchase_item->product_id]->type == 'Product')
			{
				// update inventory
				$this->db->set('quantity', "quantity + ({$purchase_item->quantity})", false);
				$this->db->where('product_id', $purchase_item->product_id);
				$this->db->where('location_id', $purchase_item->location_id);
				$this->core_model->update('inventory', 0);

				// insert stock
				$stock_record = array();
				$stock_record['location_id'] = $purchase_item->location_id;
				$stock_record['product_id'] = $purchase_item->product_id;
				$stock_record['ref_id'] = $purchase_id;
				$stock_record['type'] = 'Purchase';
				$stock_record['date'] = $purchase_record['date'];

				$stock_record['quantity_in'] = $purchase_item->quantity;

				$stock_record['location_type'] = $purchase_item_record['location_type'];
				$stock_record['location_number'] = $purchase_item_record['location_number'];
				$stock_record['location_name'] = $purchase_item_record['location_name'];
				$stock_record['location_date'] = $purchase_item_record['location_date'];
				$stock_record['location_status'] = $purchase_item_record['location_status'];

				$stock_record['product_type'] = $purchase_item_record['product_type'];
				$stock_record['product_number'] = $purchase_item_record['product_number'];
				$stock_record['product_name'] = $purchase_item_record['product_name'];
				$stock_record['product_date'] = $purchase_item_record['product_date'];
				$stock_record['product_status'] = $purchase_item_record['product_status'];

				$stock_record['ref_type'] = $purchase_item_record['purchase_type'];
				$stock_record['ref_number'] = $purchase_item_record['purchase_number'];
				$stock_record['ref_name'] = $purchase_item_record['purchase_name'];
				$stock_record['ref_date'] = $purchase_item_record['purchase_date'];
				$stock_record['ref_status'] = $purchase_item_record['purchase_status'];
				$this->core_model->insert('stock', $stock_record);
			}
		}

		if ($purchase_record['type'] == 'Cash')
		{
			// insert payment
			$payment_record = array();
			$payment_record['purchase_id'] = $purchase_id;
			$payment_record['statement_id'] = (isset($purchase_record['statement_id'])) ? $purchase_record['statement_id'] : 0;
			$payment_record['vendor_id'] = (isset($purchase_record['vendor_id'])) ? $purchase_record['vendor_id'] : 0;
			$payment_record['date'] = (isset($purchase_record['date'])) ? $purchase_record['date'] : 0;
			$payment_record['amount'] = $purchase_record['total'];

			$payment_record['purchase_type'] = (isset($purchase_record['type'])) ? $purchase_record['type'] : '';
			$payment_record['purchase_number'] = (isset($purchase_record['number'])) ? $purchase_record['number'] : '';
			$payment_record['purchase_name'] = '';
			$payment_record['purchase_date'] = (isset($purchase_record['date'])) ? $purchase_record['date'] : 0;
			$payment_record['purchase_status'] = (isset($purchase_record['status'])) ? $purchase_record['status'] : '';

			$payment_record['statement_type'] = (isset($purchase_record['statement_type'])) ? $purchase_record['statement_type'] : '';
			$payment_record['statement_number'] = (isset($purchase_record['statement_number'])) ? $purchase_record['statement_number'] : '';
			$payment_record['statement_name'] = (isset($purchase_record['statement_name'])) ? $purchase_record['statement_name'] : '';
			$payment_record['statement_date'] = (isset($purchase_record['statement_date'])) ? $purchase_record['statement_date'] : 0;
			$payment_record['statement_status'] = (isset($purchase_record['statement_status'])) ? $purchase_record['statement_status'] : '';

			$payment_record['vendor_type'] = (isset($purchase_record['vendor_type'])) ? $purchase_record['vendor_type'] : '';
			$payment_record['vendor_number'] = (isset($purchase_record['vendor_number'])) ? $purchase_record['vendor_number'] : '';
			$payment_record['vendor_name'] = (isset($purchase_record['vendor_name'])) ? $purchase_record['vendor_name'] : '';
			$payment_record['vendor_date'] = (isset($purchase_record['vendor_date'])) ? $purchase_record['vendor_date'] : 0;
			$payment_record['vendor_status'] = (isset($purchase_record['vendor_status'])) ? $purchase_record['vendor_status'] : '';
			$payment_id = $this->core_model->insert('payment', $payment_record);

			// insert transaction
			$transaction_record['purchase_id'] = $purchase_id;
			$transaction_record['statement_id'] = (isset($purchase_record['statement_id'])) ? $purchase_record['statement_id'] : 0;
			$transaction_record['vendor_id'] = (isset($purchase_record['vendor_id'])) ? $purchase_record['vendor_id'] : 0;
			$transaction_record['payment_id'] = $payment_id;

			$transaction_record['date'] = $purchase_record['date'];
			$transaction_record['credit'] = $purchase_record['total'];

			$transaction_record['purchase_type'] = (isset($purchase_record['type'])) ? $purchase_record['type'] : '';
			$transaction_record['purchase_number'] = (isset($purchase_record['number'])) ? $purchase_record['number'] : '';
			$transaction_record['purchase_name'] = '';
			$transaction_record['purchase_date'] = (isset($purchase_record['date'])) ? $purchase_record['date'] : 0;
			$transaction_record['purchase_status'] = (isset($purchase_record['status'])) ? $purchase_record['status'] : '';

			$transaction_record['statement_type'] = (isset($purchase_record['statement_type'])) ? $purchase_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($purchase_record['statement_number'])) ? $purchase_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($purchase_record['statement_name'])) ? $purchase_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($purchase_record['statement_date'])) ? $purchase_record['statement_date'] : 0;
			$transaction_record['statement_status'] = (isset($purchase_record['statement_status'])) ? $purchase_record['statement_status'] : '';

			$transaction_record['vendor_type'] = (isset($purchase_record['vendor_type'])) ? $purchase_record['vendor_type'] : '';
			$transaction_record['vendor_number'] = (isset($purchase_record['vendor_number'])) ? $purchase_record['vendor_number'] : '';
			$transaction_record['vendor_name'] = (isset($purchase_record['vendor_name'])) ? $purchase_record['vendor_name'] : '';
			$transaction_record['vendor_date'] = (isset($purchase_record['vendor_date'])) ? $purchase_record['vendor_date'] : 0;
			$transaction_record['vendor_status'] = (isset($purchase_record['vendor_status'])) ? $purchase_record['vendor_status'] : '';

			$transaction_record['payment_type'] = (isset($payment_record['type'])) ? $payment_record['type'] : '';
			$transaction_record['payment_number'] = (isset($payment_record['number'])) ? $payment_record['number'] : '';
			$transaction_record['payment_name'] = (isset($payment_record['name'])) ? $payment_record['name'] : '';
			$transaction_record['payment_date'] = (isset($payment_record['date'])) ? $payment_record['date'] : 0;
			$transaction_record['payment_status'] = (isset($payment_record['status'])) ? $payment_record['status'] : '';
			$this->core_model->insert('transaction', $transaction_record);
		}
	}

	private function _delete_purchase_item($purchase_id)
	{
		$this->db->where('purchase_id', $purchase_id);
		$arr_old_purchase_item = $this->core_model->get('purchase_item');

		foreach ($arr_old_purchase_item as $old_purchase_item)
		{
			if ($old_purchase_item->draft <= 0)
			{
				// update inventory
				$this->db->set('quantity', "quantity - ({$old_purchase_item->quantity})", false);
				$this->db->where('product_id', $old_purchase_item->product_id);
				$this->db->where('location_id', $old_purchase_item->location_id);
				$this->core_model->update('inventory', 0);
			}

			$this->core_model->delete('purchase_item', $old_purchase_item->id);
		}

		// delete stock
		$this->db->where('ref_id', $purchase_id);
		$this->db->where('type', 'purchase');
		$this->core_model->delete('stock');

		// delete payment
		$this->db->where('purchase_id', $purchase_id);
		$this->core_model->delete('payment');

		// delete transaction
		$this->db->where('purchase_id', $purchase_id);
		$this->core_model->delete('transaction');
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

	private function _get_statement()
	{
		$this->db->order_by('name');
		return $this->core_model->get('statement');
	}

	private function _get_vendor()
	{
		$this->db->order_by('name');
		return $this->core_model->get('vendor');
	}

	private function _update_purchase_item($purchase_id, $purchase_record, $arr_purchase_item)
	{
		$this->_delete_purchase_item($purchase_id);
		$this->_add_purchase_item($purchase_id, $purchase_record, $arr_purchase_item);
	}

	private function _validate_add($purchase_record)
	{
		$this->db->where('number', $purchase_record['number']);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($purchase_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $purchase_id);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		$this->db->where('purchase_id', $purchase_id);
		$count_payment = $this->core_model->count('payment');

		if ($count_payment > 0)
		{
			throw new Exception('Data cannot be deleted. This Purchase has already paid.');
		}

		$this->db->where('purchase_id', $purchase_id);
		$count_transaction = $this->core_model->count('transaction');

		if ($count_transaction > 0)
		{
			throw new Exception('Data cannot be deleted. This Purchase has already paid.');
		}
	}

	private function _validate_edit($purchase_id, $purchase_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $purchase_id);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $purchase_id);
		$this->db->where('number', $purchase_record['number']);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}