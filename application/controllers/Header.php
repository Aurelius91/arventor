<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Header extends CI_Controller
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
		$arr_data['type'] = 'Navigation';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('header_add', $arr_data);
	}

	public function edit($header_id = 0)
	{
		$acl = $this->_acl;

		if ($header_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['website']) || $acl['website']->edit <= 0)
		{
			redirect(base_url());
		}

		$header = $this->core_model->get('header', $header_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Navigation';
		$arr_data['header'] = $header;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('header_edit', $arr_data);
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
		$this->db->order_by("sort");
		$arr_header = $this->core_model->get('header');
		$arr_header_id = $this->cms_function->extract_records($arr_header, 'id');

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

		$count_header = $this->core_model->count('header');
		$count_page = ceil($count_header / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Navigation';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_header'] = $arr_header;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('header', $arr_data);
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
				throw new Exception('You have no access to add header. Please contact your administrator.');
			}

			$header_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$header_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$header_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($header_record['name']));
            $header_record['url_name'] = preg_replace("/[\s_]/", "-", $header_record['url_name']);

			$this->_validate_add($header_record);

			$header_id = $this->core_model->insert('header', $header_record);
			$header_record['id'] = $header_id;
			$header_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($header_record['id'], 'add', $header_record, array(), 'header');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('header_id' => $header_id));
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

	public function ajax_change_status($header_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($header_id <= 0)
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
				throw new Exception('You have no access to edit header. Please contact your administrator.');
			}

			$old_header = $this->core_model->get('header', $header_id);

			$old_header_record = array();

			foreach ($old_header as $key => $value)
			{
				$old_header_record[$key] = $value;
			}

			$header_record = array();

			foreach ($_POST as $k => $v)
			{
				$header_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('header', $header_id, $header_record);
			$header_record['id'] = $header_id;
			$header_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $header_record, $old_header_record, 'header');

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

	public function ajax_delete($header_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($header_id <= 0)
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
				throw new Exception('You have no access to delete header. Please contact your administrator.');
			}

			$header = $this->core_model->get('header', $header_id);
			$updated = $_POST['updated'];
			$header_record = array();

			foreach ($header as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another header. Please refresh the page.');
				}
				else
				{
					$header_record[$k] = $v;
				}
			}

			$this->_validate_delete($header_id);

			$this->core_model->delete('header', $header_id);
			$header_record['id'] = $header->id;
			$header_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($header_record['id'], 'delete', $header_record, array(), 'header');

			if ($this->_has_image > 0)
			{
				$this->db->where('header_id', $header_id);
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

	public function ajax_edit($header_id)
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
				throw new Exception('You have no access to edit header. Please contact your administrator.');
			}

			$old_header = $this->core_model->get('header', $header_id);

			$old_header_record = array();

			foreach ($old_header as $key => $value)
			{
				$old_header_record[$key] = $value;
			}

			$header_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_header_record[$k])
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
					$header_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($header_id, $header_record);

			$this->core_model->update('header', $header_id, $header_record);
			$header_record['id'] = $header_id;
			$header_record['type'] = $old_header_record['type'];
			$header_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($header_record['id'], 'edit', $header_record, $old_header_record, 'header');
			$this->cms_function->update_foreign_field(array('metatag', 'section'), $header_record, 'header');

			if ($image_id > 0)
            {
                $this->db->where('header_id', $header_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('header_id' => $header_id));
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

	public function ajax_get($header_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($header_id <= 0)
			{
				throw new Exception();
			}

			$header = $this->core_model->get('header', $header_id);

			$json['header'] = $header;
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




	private function _validate_add($header_record)
	{
		$this->db->where('name', $header_record['name']);
		$count_user = $this->core_model->count('header');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($header_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $header_id);
		$count_user = $this->core_model->count('header');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($header_id, $header_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $header_id);
		$count_user = $this->core_model->count('header');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $header_id);
		$this->db->where('name', $header_record['name']);
		$count_user = $this->core_model->count('header');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}