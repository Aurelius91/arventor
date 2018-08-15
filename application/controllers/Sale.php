<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sale extends CI_Controller
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

		if (!isset($acl['sale']) || $acl['sale']->add <= 0)
		{
			redirect(base_url());
		}

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Sale';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_customer'] = $this->_get_customer();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('sale_add', $arr_data);
	}

	public function edit($sale_id = 0)
	{
		$acl = $this->_acl;

		if ($sale_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['sale']) || $acl['sale']->edit <= 0)
		{
			redirect(base_url());
		}

		$sale = $this->core_model->get('sale', $sale_id);
		$sale->date_display = date('Y-m-d', $sale->date);
		$sale->discount_display = number_format($sale->discount, 0, '', '');
		$sale->tax_display = number_format($sale->tax, 0, '', '');
		$sale->shipping_display = number_format($sale->shipping, 0, '', '');
		$sale->deadline = $sale->date + (86400 * $sale->term);
		$sale->deadline_display = date('d F Y', $sale->deadline);

		$this->db->where('sale_id', $sale->id);
		$arr_sale_item = $this->core_model->get('sale_item');

		foreach ($arr_sale_item as $sale_item)
		{
			$sale_item->quantity_display = number_format($sale_item->quantity, 0, '', '');
			$sale_item->price_display = number_format($sale_item->price, 0, '', '');
		}

		$sale->arr_sale_item = $arr_sale_item;

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Sale';
		$arr_data['sale'] = $sale;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_customer'] = $this->_get_customer();
		$arr_data['arr_location'] = $this->_get_location();
		$arr_data['arr_product'] = $this->_get_product();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('sale_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['sale']) || $acl['sale']->list <= 0)
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
		$this->db->order_by("date DESC, id DESC");
		$arr_sale = $this->core_model->get('sale');

		foreach ($arr_sale as $sale)
		{
			$sale->date_display = date('d F Y', $sale->date);
			$sale->total = number_format($sale->total, 0, ',', '.');

			$sale->deadline = $sale->date + (86400 * $sale->term);
			$sale->deadline_display = date('d F Y', $sale->deadline);
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

		$count_sale = $this->core_model->count('sale');
		$count_page = ceil($count_sale / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Sale';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_sale'] = $arr_sale;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('sale', $arr_data);
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

			if ($this->_user->type != 'Cashier' && (!isset($acl['sale']) || $acl['sale']->add <= 0))
			{
				throw new Exception('You have no access to add sale. Please contact your administrator.');
			}

			$sale_record = array();
			$arr_sale_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'sale_item_sale_item')
				{
					$arr_sale_item = json_decode($v);
				}
				else
				{
					$sale_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$sale_record = $this->cms_function->populate_foreign_field($sale_record['location_id'], $sale_record, 'location');
			$sale_record = $this->cms_function->populate_foreign_field($sale_record['customer_id'], $sale_record, 'customer');
			$sale_record = $this->cms_function->populate_foreign_field($sale_record['statement_id'], $sale_record, 'statement');

			$sale_record['status'] = 'Pending';

			if ($sale_record['draft'] <= 0)
			{
				$sale_record['status'] = ($sale_record['type'] == 'Cash') ? 'Paid' : $sale_record['status'];
			}

			$this->_validate_add($sale_record);

			$sale_id = $this->core_model->insert('sale', $sale_record);
			$sale_record['id'] = $sale_id;
			$sale_record['last_query'] = $this->db->last_query();

			if (!isset($sale_record['number']) || (isset($sale_record['number']) && $sale_record['number'] == ''))
			{
				$sale_record['number'] = '#S' . str_pad($sale_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('sale', $sale_id, array('number' => $sale_record['number']));
			}

			$sale_record['name'] = $sale_record['number'];

			$this->cms_function->system_log($sale_record['id'], 'add', $sale_record, array(), 'sale');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('sale_id' => $sale_id));
			}

			$this->_add_sale_item($sale_id, $sale_record, $arr_sale_item);

			$json['sale_id'] = $sale_id;

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

	public function ajax_change_status($sale_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($sale_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['sale']) || $acl['sale']->edit <= 0)
			{
				throw new Exception('You have no access to edit sale. Please contact your administrator.');
			}

			$old_sale = $this->core_model->get('sale', $sale_id);

			$old_sale_record = array();

			foreach ($old_sale as $key => $value)
			{
				$old_sale_record[$key] = $value;
			}

			$sale_record = array();

			foreach ($_POST as $k => $v)
			{
				$sale_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('sale', $sale_id, $sale_record);
			$sale_record['id'] = $sale_id;
			$sale_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($sale_id, 'status', $sale_record, $old_sale_record, 'sale');

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

	public function ajax_delete($sale_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($sale_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['sale']) || $acl['sale']->delete <= 0)
			{
				throw new Exception('You have no access to delete sale. Please contact your administrator.');
			}

			$sale = $this->core_model->get('sale', $sale_id);
			$updated = $_POST['updated'];
			$sale_record = array();

			foreach ($sale as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$sale_record[$k] = $v;
				}
			}

			$this->_validate_delete($sale_id);

			$this->core_model->delete('sale', $sale_id);
			$sale_record['id'] = $sale->id;
			$sale_record['last_query'] = $this->db->last_query();
			$sale_record['name'] = $sale_record['number'];

			$this->cms_function->system_log($sale_record['id'], 'delete', $sale_record, array(), 'sale');

			if ($this->_has_image > 0)
			{
				$this->db->where('sale_id', $sale_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->_delete_sale_item($sale_id);

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

	public function ajax_edit($sale_id)
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

			if (!isset($acl['sale']) || $acl['sale']->edit <= 0)
			{
				throw new Exception('You have no access to edit sale. Please contact your administrator.');
			}

			$old_sale = $this->core_model->get('sale', $sale_id);

			$old_sale_record = array();

			foreach ($old_sale as $key => $value)
			{
				$old_sale_record[$key] = $value;
			}

			$sale_record = array();
			$arr_sale_item = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				elseif ($k == 'sale_item_sale_item')
				{
					$arr_sale_item = json_decode($v);
				}
				else
				{
					$sale_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$sale_record = $this->cms_function->populate_foreign_field($sale_record['location_id'], $sale_record, 'location');
			$sale_record = $this->cms_function->populate_foreign_field($sale_record['customer_id'], $sale_record, 'customer');
			$sale_record = $this->cms_function->populate_foreign_field($sale_record['statement_id'], $sale_record, 'statement');

			$sale_record['status'] = 'Pending';

			if ($sale_record['draft'] <= 0)
			{
				$sale_record['status'] = ($sale_record['type'] == 'Cash') ? 'Paid' : $sale_record['status'];
			}

			$this->_validate_edit($sale_id, $sale_record);

			$this->core_model->update('sale', $sale_id, $sale_record);
			$sale_record['id'] = $sale_id;
			$sale_record['last_query'] = $this->db->last_query();
			$sale_record['name'] = $sale_record['number'];

			$this->cms_function->system_log($sale_record['id'], 'edit', $sale_record, $old_sale_record, 'sale');
			$this->cms_function->update_foreign_field(array('transaction'), $sale_record, 'sale');

			if ($image_id > 0)
            {
                $this->db->where('sale_id', $sale_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('sale_id' => $sale_id));
            }

            $this->_update_sale_item($sale_id, $sale_record, $arr_sale_item);

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

	public function ajax_get($sale_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($sale_id <= 0)
			{
				throw new Exception();
			}

			$sale = $this->core_model->get('sale', $sale_id);

			$json['sale'] = $sale;
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




	private function _add_sale_item($sale_id, $sale_record, $arr_sale_item)
	{
		// get product
		$arr_product = $this->core_model->get('product');
		$arr_product_lookup = array();

		foreach ($arr_product as $product)
		{
			$arr_product_lookup[$product->id] = clone $product;
		}

		foreach ($arr_sale_item as $sale_item)
		{
			$sale_item_record = array();
			$sale_item_record['customer_id'] = $sale_record['customer_id'];
			$sale_item_record['location_id'] = $sale_record['location_id'];
			$sale_item_record['product_id'] = $sale_item->product_id;
			$sale_item_record['sale_id'] = $sale_id;

			$sale_item_record['price'] = $sale_item->price;
			$sale_item_record['quantity'] = $sale_item->quantity;
			$sale_item_record['draft'] = $sale_record['draft'];

			$sale_item_record['customer_type'] = $sale_record['customer_type'];
			$sale_item_record['customer_number'] = $sale_record['customer_number'];
			$sale_item_record['customer_name'] = $sale_record['customer_name'];
			$sale_item_record['customer_date'] = $sale_record['customer_date'];
			$sale_item_record['customer_status'] = $sale_record['customer_status'];

			$sale_item_record['location_type'] = $sale_record['location_type'];
			$sale_item_record['location_number'] = $sale_record['location_number'];
			$sale_item_record['location_name'] = $sale_record['location_name'];
			$sale_item_record['location_date'] = $sale_record['location_date'];
			$sale_item_record['location_status'] = $sale_record['location_status'];

			$sale_item_record['product_type'] = (isset($arr_product_lookup[$sale_item->product_id])) ? $arr_product_lookup[$sale_item->product_id]->type : '';
			$sale_item_record['product_number'] = (isset($arr_product_lookup[$sale_item->product_id])) ? $arr_product_lookup[$sale_item->product_id]->number : '';
			$sale_item_record['product_name'] = (isset($arr_product_lookup[$sale_item->product_id])) ? $arr_product_lookup[$sale_item->product_id]->name : '';
			$sale_item_record['product_date'] = (isset($arr_product_lookup[$sale_item->product_id])) ? $arr_product_lookup[$sale_item->product_id]->date : '';
			$sale_item_record['product_status'] = (isset($arr_product_lookup[$sale_item->product_id])) ? $arr_product_lookup[$sale_item->product_id]->status : '';

			$sale_item_record['sale_type'] = (isset($sale_record['type'])) ? $sale_record['type'] : '';
			$sale_item_record['sale_number'] = (isset($sale_record['number'])) ? $sale_record['number'] : '';
			$sale_item_record['sale_name'] = '';
			$sale_item_record['sale_date'] = (isset($sale_record['date'])) ? $sale_record['date'] : 0;
			$sale_item_record['sale_status'] = (isset($sale_record['status'])) ? $sale_record['status'] : '';
			$this->core_model->insert('sale_item', $sale_item_record);

			if ($sale_record['draft'] <= 0 && $arr_product_lookup[$sale_item->product_id]->type == 'Product')
			{
				// update inventory
				$this->db->set('quantity', "quantity - ({$sale_item->quantity})", false);
				$this->db->where('product_id', $sale_item->product_id);
				$this->db->where('location_id', $sale_item->location_id);
				$this->core_model->update('inventory', 0);

				// insert stock
				$stock_record = array();
				$stock_record['location_id'] = $sale_item->location_id;
				$stock_record['product_id'] = $sale_item->product_id;
				$stock_record['ref_id'] = $sale_id;
				$stock_record['type'] = 'Sale';
				$stock_record['date'] = $sale_record['date'];

				$stock_record['quantity_out'] = $sale_item->quantity;

				$stock_record['location_type'] = $sale_item_record['location_type'];
				$stock_record['location_number'] = $sale_item_record['location_number'];
				$stock_record['location_name'] = $sale_item_record['location_name'];
				$stock_record['location_date'] = $sale_item_record['location_date'];
				$stock_record['location_status'] = $sale_item_record['location_status'];

				$stock_record['product_type'] = $sale_item_record['product_type'];
				$stock_record['product_number'] = $sale_item_record['product_number'];
				$stock_record['product_name'] = $sale_item_record['product_name'];
				$stock_record['product_date'] = $sale_item_record['product_date'];
				$stock_record['product_status'] = $sale_item_record['product_status'];

				$stock_record['ref_type'] = $sale_item_record['sale_type'];
				$stock_record['ref_number'] = $sale_item_record['sale_number'];
				$stock_record['ref_name'] = $sale_item_record['sale_name'];
				$stock_record['ref_date'] = $sale_item_record['sale_date'];
				$stock_record['ref_status'] = $sale_item_record['sale_status'];
				$this->core_model->insert('stock', $stock_record);
			}
		}

		if ($sale_record['type'] == 'Cash')
		{
			// insert payment
			$payment_record = array();
			$payment_record['sale_id'] = $sale_id;
			$payment_record['statement_id'] = (isset($sale_record['statement_id'])) ? $sale_record['statement_id'] : 0;
			$payment_record['customer_id'] = (isset($sale_record['customer_id'])) ? $sale_record['customer_id'] : 0;
			$payment_record['date'] = (isset($sale_record['date'])) ? $sale_record['date'] : 0;
			$payment_record['amount'] = $sale_record['total'];

			$payment_record['sale_type'] = (isset($sale_record['type'])) ? $sale_record['type'] : '';
			$payment_record['sale_number'] = (isset($sale_record['number'])) ? $sale_record['number'] : '';
			$payment_record['sale_name'] = '';
			$payment_record['sale_date'] = (isset($sale_record['date'])) ? $sale_record['date'] : 0;
			$payment_record['sale_status'] = (isset($sale_record['status'])) ? $sale_record['status'] : '';

			$payment_record['statement_type'] = (isset($sale_record['statement_type'])) ? $sale_record['statement_type'] : '';
			$payment_record['statement_number'] = (isset($sale_record['statement_number'])) ? $sale_record['statement_number'] : '';
			$payment_record['statement_name'] = (isset($sale_record['statement_name'])) ? $sale_record['statement_name'] : '';
			$payment_record['statement_date'] = (isset($sale_record['statement_date'])) ? $sale_record['statement_date'] : 0;
			$payment_record['statement_status'] = (isset($sale_record['statement_status'])) ? $sale_record['statement_status'] : '';

			$payment_record['customer_type'] = (isset($sale_record['customer_type'])) ? $sale_record['customer_type'] : '';
			$payment_record['customer_number'] = (isset($sale_record['customer_number'])) ? $sale_record['customer_number'] : '';
			$payment_record['customer_name'] = (isset($sale_record['customer_name'])) ? $sale_record['customer_name'] : '';
			$payment_record['customer_date'] = (isset($sale_record['customer_date'])) ? $sale_record['customer_date'] : 0;
			$payment_record['customer_status'] = (isset($sale_record['customer_status'])) ? $sale_record['customer_status'] : '';
			$payment_id = $this->core_model->insert('payment', $payment_record);

			// insert transaction
			$transaction_record['sale_id'] = $sale_id;
			$transaction_record['statement_id'] = (isset($sale_record['statement_id'])) ? $sale_record['statement_id'] : 0;
			$transaction_record['customer_id'] = (isset($sale_record['customer_id'])) ? $sale_record['customer_id'] : 0;
			$transaction_record['payment_id'] = $payment_id;

			$transaction_record['date'] = $sale_record['date'];
			$transaction_record['debit'] = $sale_record['total'];

			$transaction_record['sale_type'] = (isset($sale_record['type'])) ? $sale_record['type'] : '';
			$transaction_record['sale_number'] = (isset($sale_record['number'])) ? $sale_record['number'] : '';
			$transaction_record['sale_name'] = '';
			$transaction_record['sale_date'] = (isset($sale_record['date'])) ? $sale_record['date'] : 0;
			$transaction_record['sale_status'] = (isset($sale_record['status'])) ? $sale_record['status'] : '';

			$transaction_record['statement_type'] = (isset($sale_record['statement_type'])) ? $sale_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($sale_record['statement_number'])) ? $sale_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($sale_record['statement_name'])) ? $sale_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($sale_record['statement_date'])) ? $sale_record['statement_date'] : 0;
			$transaction_record['statement_status'] = (isset($sale_record['statement_status'])) ? $sale_record['statement_status'] : '';

			$transaction_record['customer_type'] = (isset($sale_record['customer_type'])) ? $sale_record['customer_type'] : '';
			$transaction_record['customer_number'] = (isset($sale_record['customer_number'])) ? $sale_record['customer_number'] : '';
			$transaction_record['customer_name'] = (isset($sale_record['customer_name'])) ? $sale_record['customer_name'] : '';
			$transaction_record['customer_date'] = (isset($sale_record['customer_date'])) ? $sale_record['customer_date'] : 0;
			$transaction_record['customer_status'] = (isset($sale_record['customer_status'])) ? $sale_record['customer_status'] : '';

			$transaction_record['payment_type'] = (isset($payment_record['type'])) ? $payment_record['type'] : '';
			$transaction_record['payment_number'] = (isset($payment_record['number'])) ? $payment_record['number'] : '';
			$transaction_record['payment_name'] = (isset($payment_record['name'])) ? $payment_record['name'] : '';
			$transaction_record['payment_date'] = (isset($payment_record['date'])) ? $payment_record['date'] : 0;
			$transaction_record['payment_status'] = (isset($payment_record['status'])) ? $payment_record['status'] : '';
			$this->core_model->insert('transaction', $transaction_record);
		}
	}

	private function _delete_sale_item($sale_id)
	{
		$this->db->where('sale_id', $sale_id);
		$arr_old_sale_item = $this->core_model->get('sale_item');

		foreach ($arr_old_sale_item as $old_sale_item)
		{
			if ($old_sale_item->draft <= 0)
			{
				// update inventory
				$this->db->set('quantity', "quantity + ({$old_sale_item->quantity})", false);
				$this->db->where('product_id', $old_sale_item->product_id);
				$this->db->where('location_id', $old_sale_item->location_id);
				$this->core_model->update('inventory', 0);
			}

			$this->core_model->delete('sale_item', $old_sale_item->id);
		}

		// delete stock
		$this->db->where('ref_id', $sale_id);
		$this->db->where('type', 'Sale');
		$this->core_model->delete('stock');

		// delete payment
		$this->db->where('sale_id', $sale_id);
		$this->core_model->delete('payment');

		// delete transaction
		$this->db->where('sale_id', $sale_id);
		$this->core_model->delete('transaction');
	}

	private function _get_customer()
	{
		$this->db->order_by('name');
		return $this->core_model->get('customer');
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

	private function _update_sale_item($sale_id, $sale_record, $arr_sale_item)
	{
		$this->_delete_sale_item($sale_id);
		$this->_add_sale_item($sale_id, $sale_record, $arr_sale_item);
	}

	private function _validate_add($sale_record)
	{
		$this->db->where('number', $sale_record['number']);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($sale_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $sale_id);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Data cannot be deleted');
		}

		$this->db->where('sale_id', $sale_id);
		$count_transaction = $this->core_model->count('transaction');

		if ($count_transaction > 0)
		{
			throw new Exception('Data cannot be deleted. This Sale has already paid.');
		}
	}

	private function _validate_edit($sale_id, $sale_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $sale_id);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $sale_id);
		$this->db->where('number', $sale_record['number']);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}