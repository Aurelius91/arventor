<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller
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




	public function account()
	{
		$acl = $this->_acl;

		$user = $this->core_model->get('user', $this->_user->id);
		$user->address = $this->cms_function->trim_text($user->address);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'User';
		$arr_data['user'] = $user;
		$arr_data['arr_module'] = $this->_generate_module();
		$arr_data['arr_location'] = $this->_generate_location();
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('account', $arr_data);
	}

	public function add()
	{
		$acl = $this->_acl;

		if (!isset($acl['user']) || $acl['user']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'User';
		$arr_data['arr_module'] = $this->_generate_module();
		$arr_data['arr_location'] = $this->_generate_location();
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('user_add', $arr_data);
	}

	public function edit($user_id = 0)
	{
		$acl = $this->_acl;

		if ($user_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['user']) || $acl['user']->edit <= 0)
		{
			redirect(base_url());
		}

		$user = $this->core_model->get('user', $user_id);
		$user->address = $this->cms_function->trim_text($user->address);

		$this->db->select('module_id, add, delete, edit, list');
		$this->db->where('user_id', $user->id);
		$user->arr_user_access = $this->core_model->get('user_access');

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'User';
		$arr_data['user'] = $user;
		$arr_data['arr_module'] = $this->_generate_module();
		$arr_data['arr_location'] = $this->_generate_location();
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('user_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['user']) || $acl['user']->list <= 0)
		{
			redirect(base_url());
		}

		$query = ($query != '') ? base64_decode($query) : '';

		$this->db->where('id >', 1);

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
		$arr_user = $this->core_model->get('user');

		foreach ($arr_user as $user)
		{
			$user->status = ($user->active > 0) ? 'Active' : 'Inactive';
		}

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

		$count_user = $this->core_model->count('user');
		$count_page = ceil($count_user / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'User';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_user'] = $arr_user;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('user', $arr_data);
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

			if (!isset($acl['user']) || $acl['user']->add <= 0)
			{
				throw new Exception('You have no access to add User. Please contact your administrator.');
			}

			$user_record = array();
			$arr_user_access = array();

			foreach ($_POST as $k => $v)
			{
				if ($k == 'user_access_user_access')
				{
					$arr_user_access = json_decode($v);
				}
				elseif ($k == 'password')
				{
					$user_record[$k] = ($v == '') ? '' : md5($v);
				}
				else
				{
					$user_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			if ($user_record['location_id'] > 0)
			{
				$user_record = $this->cms_function->populate_foreign_field($user_record['location_id'], $user_record, 'location');
			}

			$this->_validate_add($user_record);

			$user_id = $this->core_model->insert('user', $user_record);
			$user_record['id'] = $user_id;
			$user_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($user_record['id'], 'add', $user_record, array(), 'User');

			// Insert User Access
			if ($user_record['type'] != 'Cashier')
			{
				$this->_add_user_access($user_id, $arr_user_access, $user_record);
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

	public function ajax_change_status($user_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($user_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['user']) || $acl['user']->edit <= 0)
			{
				throw new Exception('You have no access to edit User. Please contact your administrator.');
			}

			$old_user = $this->core_model->get('user', $user_id);

			$old_user_record = array();

			foreach ($old_user as $key => $value)
			{
				$old_user_record[$key] = $value;
			}

			$user_record = array();

			foreach ($_POST as $k => $v)
			{
				$user_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('user', $user_id, $user_record);
			$user_record['id'] = $user_id;
			$user_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($user_id, 'status', $user_record, $old_user_record, 'User');

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

	public function ajax_delete($user_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($user_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['user']) || $acl['user']->delete <= 0)
			{
				throw new Exception('You have no access to delete User. Please contact your administrator.');
			}

			$user = $this->core_model->get('user', $user_id);
			$updated = $_POST['updated'];
			$user_record = array();

			foreach ($user as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another user. Please refresh the page.');
				}
				else
				{
					$user_record[$k] = $v;
				}
			}

			$this->_validate_delete($user_id);

			$this->core_model->delete('user', $user_id);
			$user_record['id'] = $user->id;
			$user_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($user_record['id'], 'delete', $user_record, array(), 'User');
			$this->_delete_user_access($user_id);

			if ($this->_has_image > 0)
			{
				$this->db->where('user_id', $user_id);
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

	public function ajax_edit($user_id)
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

			if (!isset($acl['user']) || $acl['user']->edit <= 0)
			{
				throw new Exception('You have no access to edit User. Please contact your administrator.');
			}

			$old_user = $this->core_model->get('user', $user_id);

			$old_user_record = array();

			foreach ($old_user as $key => $value)
			{
				$old_user_record[$key] = $value;
			}

			$user_record = array();
			$arr_user_access = array();

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated' && $v != $old_user_record[$k])
				{
					throw new Exception('This data has been updated by another user. Please refresh the page.');
				}
				elseif ($k == 'user_access_user_access')
				{
					$arr_user_access = json_decode($v);
				}
				elseif ($k == 'password')
				{
					$user_record[$k] = ($v == '') ? $old_user_record['password'] : md5($v);
				}
				else
				{
					$user_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			if ($user_record['location_id'] > 0)
			{
				$user_record = $this->cms_function->populate_foreign_field($user_record['location_id'], $user_record, 'location');
			}
			else
			{
				$user_record['location_type'] = '';
				$user_record['location_number'] = '';
				$user_record['location_name'] = '';
				$user_record['location_date'] = 0;
				$user_record['location_status'] = '';
			}

			$this->_validate_edit($user_id, $user_record);

			$this->core_model->update('user', $user_id, $user_record);
			$user_record['id'] = $user_id;
			$user_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($user_record['id'], 'edit', $user_record, $old_user_record, 'User');

			// Insert User Access
			if ($user_record['type'] != 'Cashier')
			{
				if (count($arr_user_access) > 0)
				{
					$this->_add_user_access($user_id, $arr_user_access, $user_record);
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




	private function _add_user_access($user_id, $arr_user_access, $user_record)
	{
		$this->_delete_user_access($user_id);

		$this->db->select('id, type, number, name, date, status');
		$arr_module = $this->core_model->get('module');

		$arr_module_lookup = array();
		$arr_batch_user_access_record = array();

		foreach ($arr_module as $module)
		{
			$arr_module_lookup[$module->id] = clone $module;
		}

		foreach ($arr_user_access as $user_access)
		{
			$user_access_record = array();

			foreach ($user_access as $k => $v)
			{
				$user_access_record[$k] = $v;
 			}

			$user_access_record['user_id'] = $user_id;

			$user_access_record['user_type'] = (isset($user_record['type'])) ? $user_record['type'] : '';
			$user_access_record['user_number'] = (isset($user_record['number'])) ? $user_record['number'] : '';
			$user_access_record['user_name'] = (isset($user_record['name'])) ? $user_record['name'] : '';
			$user_access_record['user_date'] = (isset($user_record['date'])) ? $user_record['date'] : 0;
			$user_access_record['user_status'] = (isset($user_record['status'])) ? $user_record['status'] : '';

			$user_access_record['module_type'] = (isset($arr_module_lookup[$user_access->module_id])) ? $arr_module_lookup[$user_access->module_id]->type : '';
			$user_access_record['module_number'] = (isset($arr_module_lookup[$user_access->module_id])) ? $arr_module_lookup[$user_access->module_id]->number : '';
			$user_access_record['module_name'] = (isset($arr_module_lookup[$user_access->module_id])) ? $arr_module_lookup[$user_access->module_id]->name : '';
			$user_access_record['module_date'] = (isset($arr_module_lookup[$user_access->module_id])) ? $arr_module_lookup[$user_access->module_id]->date : 0;
			$user_access_record['module_status'] = (isset($arr_module_lookup[$user_access->module_id])) ? $arr_module_lookup[$user_access->module_id]->status : '';

			$user_access_record['created'] = null;
			$user_access_record['author_id'] = $this->_user->id;
			$user_access_record['author_name'] = $this->_user->name;

			$arr_batch_user_access_record[] = $user_access_record;
		}

		if (count($arr_batch_user_access_record) > 0)
		{
			$this->db->insert_batch('user_access', $arr_batch_user_access_record);
		}
	}

	private function _delete_user_access($user_id)
	{
		$this->db->where('user_id', $user_id);
		$this->core_model->delete('user_access');
	}

	private function _generate_location()
	{
		$this->db->order_by('name');
		return $this->core_model->get('location');
	}

	private function _generate_module()
	{
		$this->db->where($this->_setting->system_product . ' >', 0);
		$this->db->order_by('sort, number');
		$arr_module = $this->core_model->get('module');
		$arr_module_type = $this->cms_function->extract_records($arr_module, 'type');

		$arr_module_lookup = array();

		foreach ($arr_module_type as $module_type)
		{
			foreach ($arr_module as $module)
			{
				if ($module_type != $module->type)
				{
					continue;
				}

				$arr_module_lookup[$module_type][] = clone $module;
			}
		}

		return $arr_module_lookup;
	}

	private function _validate_add($user_record)
	{
		$this->db->where('name', $user_record['name']);
		$count_user = $this->core_model->count('user');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($user_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $user_id);
		$count_user = $this->core_model->count('user');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($user_id, $user_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $user_id);
		$count_user = $this->core_model->count('user');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $user_id);
		$this->db->where('name', $user_record['name']);
		$count_user = $this->core_model->count('user');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}