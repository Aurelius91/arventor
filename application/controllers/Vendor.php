<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vendor extends CI_Controller
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

		if (!isset($acl['vendor']) || $acl['vendor']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Vendor';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('vendor_add', $arr_data);
	}

	public function edit($vendor_id = 0)
	{
		$acl = $this->_acl;

		if ($vendor_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['vendor']) || $acl['vendor']->edit <= 0)
		{
			redirect(base_url());
		}

		$vendor = $this->core_model->get('vendor', $vendor_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Vendor';
		$arr_data['vendor'] = $vendor;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('vendor_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['vendor']) || $acl['vendor']->list <= 0)
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
		$arr_vendor = $this->core_model->get('vendor');

		if ($query != '')
		{
			$this->db->like('name', $query);
		}

		$this->db->where('id >', 1);

		if ($filter == 'all')
		{
			$this->db->like('name', $query);
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$count_vendor = $this->core_model->count('vendor');
		$count_page = ceil($count_vendor / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Vendor';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_vendor'] = $arr_vendor;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('vendor', $arr_data);
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

			if (!isset($acl['vendor']) || $acl['vendor']->add <= 0)
			{
				throw new Exception('You have no access to add vendor. Please contact your administrator.');
			}

			$vendor_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$vendor_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_add($vendor_record);

			$vendor_id = $this->core_model->insert('vendor', $vendor_record);
			$vendor_record['id'] = $vendor_id;
			$vendor_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($vendor_record['id'], 'add', $vendor_record, array(), 'vendor');

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

	public function ajax_change_status($vendor_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($vendor_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['vendor']) || $acl['vendor']->edit <= 0)
			{
				throw new Exception('You have no access to edit vendor. Please contact your administrator.');
			}

			$old_vendor = $this->core_model->get('vendor', $vendor_id);

			$old_vendor_record = array();

			foreach ($old_vendor as $key => $value)
			{
				$old_vendor_record[$key] = $value;
			}

			$vendor_record = array();

			foreach ($_POST as $k => $v)
			{
				$vendor_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('vendor', $vendor_id, $vendor_record);
			$vendor_record['id'] = $vendor_id;
			$vendor_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($vendor_id, 'status', $vendor_record, $old_vendor_record, 'vendor');

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

	public function ajax_delete($vendor_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($vendor_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['vendor']) || $acl['vendor']->delete <= 0)
			{
				throw new Exception('You have no access to delete vendor. Please contact your administrator.');
			}

			$vendor = $this->core_model->get('vendor', $vendor_id);
			$updated = $_POST['updated'];
			$vendor_record = array();

			foreach ($vendor as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another User. Please refresh the page.');
				}
				else
				{
					$vendor_record[$k] = $v;
				}
			}

			$this->_validate_delete($vendor_id);

			$this->core_model->delete('vendor', $vendor_id);
			$vendor_record['id'] = $vendor->id;
			$vendor_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($vendor_record['id'], 'delete', $vendor_record, array(), 'vendor');

			if ($this->_has_image > 0)
			{
				$this->db->where('vendor_id', $vendor_id);
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

	public function ajax_edit($vendor_id)
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

			if (!isset($acl['vendor']) || $acl['vendor']->edit <= 0)
			{
				throw new Exception('You have no access to edit vendor. Please contact your administrator.');
			}

			$old_vendor = $this->core_model->get('vendor', $vendor_id);

			$old_vendor_record = array();

			foreach ($old_vendor as $key => $value)
			{
				$old_vendor_record[$key] = $value;
			}

			$vendor_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$vendor_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($vendor_id, $vendor_record);

			$this->core_model->update('vendor', $vendor_id, $vendor_record);
			$vendor_record['id'] = $vendor_id;
			$vendor_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($vendor_record['id'], 'edit', $vendor_record, $old_vendor_record, 'vendor');
			$this->cms_function->update_foreign_field(array('payment', 'purchase', 'purchase_item', 'transaction'), $vendor_record, 'vendor');

			if ($image_id > 0)
            {
                $this->db->where('vendor_id', $vendor_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('vendor_id' => $vendor_id));
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

	public function ajax_get($vendor_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($vendor_id <= 0)
			{
				throw new Exception();
			}

			$vendor = $this->core_model->get('vendor', $vendor_id);

			$json['vendor'] = $vendor;
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




	private function _validate_add($vendor_record)
	{
		$this->db->where('name', $vendor_record['name']);
		$count_vendor = $this->core_model->count('vendor');

		if ($count_vendor > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($vendor_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $vendor_id);
		$count_vendor = $this->core_model->count('vendor');

		if ($count_vendor > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}

		$this->db->where('vendor_id', $vendor_id);
		$count_purchase = $this->core_model->count('purchase');

		if ($count_purchase > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($vendor_id, $vendor_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $vendor_id);
		$count_vendor = $this->core_model->count('vendor');

		if ($count_vendor > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $vendor_id);
		$this->db->where('name', $vendor_record['name']);
		$count_vendor = $this->core_model->count('vendor');

		if ($count_vendor > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}