<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Metatag extends CI_Controller
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

		if (!isset($acl['website']) || $acl['website']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Metatag';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('metatag_add', $arr_data);
	}

	public function edit($metatag_id = 0)
	{
		$acl = $this->_acl;

		if ($metatag_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['website']) || $acl['website']->edit <= 0)
		{
			redirect(base_url());
		}

		$metatag = $this->core_model->get('metatag', $metatag_id);
		$metatag->address = $this->cms_function->trim_text($metatag->address);

		$this->db->select('module_id, add, delete, edit, list');
		$this->db->where('metatag_id', $metatag->id);
		$metatag->arr_metatag_access = $this->core_model->get('metatag_access');

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Metatag';
		$arr_data['metatag'] = $metatag;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('metatag_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['website']) || $acl['website']->list <= 0)
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
		$this->db->order_by("id");
		$arr_metatag = $this->core_model->get('metatag');

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

		$count_metatag = $this->core_model->count('metatag');
		$count_page = ceil($count_metatag / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Metatag';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_metatag'] = $arr_metatag;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('metatag', $arr_data);
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

			if (!isset($acl['website']) || $acl['website']->add <= 0)
			{
				throw new Exception('You have no access to add metatag. Please contact your administrator.');
			}

			$metatag_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$metatag_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$metatag_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($metatag_record['name']));
            $metatag_record['url_name'] = preg_replace("/[\s_]/", "-", $metatag_record['url_name']);

			$this->_validate_add($metatag_record);

			$metatag_id = $this->core_model->insert('metatag', $metatag_record);
			$metatag_record['id'] = $metatag_id;
			$metatag_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($metatag_record['id'], 'add', $metatag_record, array(), 'metatag');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('metatag_id' => $metatag_id));
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

	public function ajax_change_status($metatag_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($metatag_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['website']) || $acl['website']->edit <= 0)
			{
				throw new Exception('You have no access to edit metatag. Please contact your administrator.');
			}

			$old_metatag = $this->core_model->get('metatag', $metatag_id);

			$old_metatag_record = array();

			foreach ($old_metatag as $key => $value)
			{
				$old_metatag_record[$key] = $value;
			}

			$metatag_record = array();

			foreach ($_POST as $k => $v)
			{
				$metatag_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('metatag', $metatag_id, $metatag_record);
			$metatag_record['id'] = $metatag_id;
			$metatag_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $metatag_record, $old_metatag_record, 'metatag');

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

	public function ajax_delete($metatag_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($metatag_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['website']) || $acl['website']->delete <= 0)
			{
				throw new Exception('You have no access to delete metatag. Please contact your administrator.');
			}

			$metatag = $this->core_model->get('metatag', $metatag_id);
			$updated = $_POST['updated'];
			$metatag_record = array();

			foreach ($metatag as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another metatag. Please refresh the page.');
				}
				else
				{
					$metatag_record[$k] = $v;
				}
			}

			$this->_validate_delete($metatag_id);

			$this->core_model->delete('metatag', $metatag_id);
			$metatag_record['id'] = $metatag->id;
			$metatag_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($metatag_record['id'], 'delete', $metatag_record, array(), 'metatag');

			if ($this->_has_image > 0)
			{
				$this->db->where('metatag_id', $metatag_id);
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

	public function ajax_edit($metatag_id)
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

			if (!isset($acl['website']) || $acl['website']->edit <= 0)
			{
				throw new Exception('You have no access to edit metatag. Please contact your administrator.');
			}

			$old_metatag = $this->core_model->get('metatag', $metatag_id);

			$old_metatag_record = array();

			foreach ($old_metatag as $key => $value)
			{
				$old_metatag_record[$key] = $value;
			}

			$metatag_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_metatag_record[$k])
					{
						throw new Exception('This data has been updated by another user. Please refresh the page.');
					}
				}
				elseif ($k == 'image_id')
                {
                    $image_id = $v;
                }
				else
				{
					$metatag_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($metatag_id, $metatag_record);

			$this->core_model->update('metatag', $metatag_id, $metatag_record);
			$metatag_record['id'] = $metatag_id;
			$metatag_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($metatag_record['id'], 'edit', $metatag_record, $old_metatag_record, 'metatag');

			if ($image_id > 0)
            {
                $this->db->where('metatag_id', $metatag_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('metatag_id' => $metatag_id));
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

	public function ajax_get($metatag_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($metatag_id <= 0)
			{
				throw new Exception();
			}

			$metatag = $this->core_model->get('metatag', $metatag_id);

			$json['metatag'] = $metatag;
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




	private function _validate_add($metatag_record)
	{
		$this->db->where('name', $metatag_record['name']);
		$count_user = $this->core_model->count('metatag');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($metatag_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $metatag_id);
		$count_user = $this->core_model->count('metatag');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($metatag_id, $metatag_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $metatag_id);
		$count_user = $this->core_model->count('metatag');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $metatag_id);
		$this->db->where('name', $metatag_record['name']);
		$count_user = $this->core_model->count('metatag');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}