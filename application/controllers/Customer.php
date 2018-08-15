<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Customer extends CI_Controller
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

		if (!isset($acl['customer']) || $acl['customer']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Customer';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('customer_add', $arr_data);
	}

	public function edit($customer_id = 0)
	{
		$acl = $this->_acl;

		if ($customer_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['customer']) || $acl['customer']->edit <= 0)
		{
			redirect(base_url());
		}

		$customer = $this->core_model->get('customer', $customer_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Customer';
		$arr_data['customer'] = $customer;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('customer_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['customer']) || $acl['customer']->list <= 0)
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
		$arr_customer = $this->core_model->get('customer');

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

		$count_customer = $this->core_model->count('customer');
		$count_page = ceil($count_customer / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Customer';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_customer'] = $arr_customer;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('customer', $arr_data);
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

			if (!isset($acl['customer']) || $acl['customer']->add <= 0)
			{
				throw new Exception('You have no access to add customer. Please contact your administrator.');
			}

			$customer_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$customer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_add($customer_record);

			$customer_id = $this->core_model->insert('customer', $customer_record);
			$customer_record['id'] = $customer_id;
			$customer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($customer_record['id'], 'add', $customer_record, array(), 'customer');

			if ($image_id > 0)
			{
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

	public function ajax_change_status($customer_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($customer_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['customer']) || $acl['customer']->edit <= 0)
			{
				throw new Exception('You have no access to edit customer. Please contact your administrator.');
			}

			$old_customer = $this->core_model->get('customer', $customer_id);

			$old_customer_record = array();

			foreach ($old_customer as $key => $value)
			{
				$old_customer_record[$key] = $value;
			}

			$customer_record = array();

			foreach ($_POST as $k => $v)
			{
				$customer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('customer', $customer_id, $customer_record);
			$customer_record['id'] = $customer_id;
			$customer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($customer_id, 'status', $customer_record, $old_customer_record, 'customer');

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

	public function ajax_delete($customer_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($customer_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['customer']) || $acl['customer']->delete <= 0)
			{
				throw new Exception('You have no access to delete customer. Please contact your administrator.');
			}

			$customer = $this->core_model->get('customer', $customer_id);
			$updated = $_POST['updated'];
			$customer_record = array();

			foreach ($customer as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$customer_record[$k] = $v;
				}
			}

			$this->_validate_delete($customer_id);

			$this->core_model->delete('customer', $customer_id);
			$customer_record['id'] = $customer->id;
			$customer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($customer_record['id'], 'delete', $customer_record, array(), 'customer');

			if ($this->_has_image > 0)
			{
				$this->db->where('customer_id', $customer_id);
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

	public function ajax_edit($customer_id)
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

			if (!isset($acl['customer']) || $acl['customer']->edit <= 0)
			{
				throw new Exception('You have no access to edit customer. Please contact your administrator.');
			}

			$old_customer = $this->core_model->get('customer', $customer_id);

			$old_customer_record = array();

			foreach ($old_customer as $key => $value)
			{
				$old_customer_record[$key] = $value;
			}

			$customer_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$customer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($customer_id, $customer_record);

			$this->core_model->update('customer', $customer_id, $customer_record);
			$customer_record['id'] = $customer_id;
			$customer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($customer_record['id'], 'edit', $customer_record, $old_customer_record, 'customer');
			$this->cms_function->update_foreign_field(array('payment','sale', 'sale_item', 'transaction'), $customer_record, 'customer');

			if ($image_id > 0)
            {
                $this->db->where('customer_id', $customer_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('customer_id' => $customer_id));
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

	public function ajax_get($customer_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($customer_id <= 0)
			{
				throw new Exception();
			}

			$customer = $this->core_model->get('customer', $customer_id);

			$json['customer'] = $customer;
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




	private function _validate_add($customer_record)
	{
		$this->db->where('name', $customer_record['name']);
		$count_customer = $this->core_model->count('customer');

		if ($count_customer > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($customer_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $customer_id);
		$count_customer = $this->core_model->count('customer');

		if ($count_customer > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}

		$this->db->where('customer_id', $customer_id);
		$count_sale = $this->core_model->count('sale');

		if ($count_sale > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($customer_id, $customer_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $customer_id);
		$count_customer = $this->core_model->count('customer');

		if ($count_customer > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $customer_id);
		$this->db->where('name', $customer_record['name']);
		$count_customer = $this->core_model->count('customer');

		if ($count_customer > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}