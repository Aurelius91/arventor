<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Section extends CI_Controller
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

			$this->_has_image = 1;
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
		$arr_data['type'] = 'section';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_category'] = $this->_get_category();

		$this->load->view('html', $arr_data);
		$this->load->view('section_add', $arr_data);
	}

	public function edit($section_id = 0)
	{
		$acl = $this->_acl;

		if ($section_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['website']) || $acl['website']->edit <= 0)
		{
			redirect(base_url());
		}

		$section = $this->core_model->get('section', $section_id);
		$section->image_name = '';

		$this->db->where('section_id', $section->id);
		$arr_image = $this->core_model->get('image');

		if (count($arr_image) > 0)
		{
			$section->image_name = ($arr_image[0]->name != '') ? $arr_image[0]->name : $arr_image[0]->id . '.' . $arr_image[0]->ext;
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'section';
		$arr_data['section'] = $section;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_category'] = $this->_get_category();

		$this->load->view('html', $arr_data);
		$this->load->view('section_edit', $arr_data);
	}

	public function view($header_id = 0, $page = 1)
	{
		$acl = $this->_acl;

		if (!isset($acl['website']) || $acl['website']->list <= 0)
		{
			redirect(base_url());
		}

		$header = $this->core_model->get('header', $header_id);

		if ($header_id > 0)
		{
			$this->db->where('header_id', $header_id);
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("id");
		$arr_section = $this->core_model->get('section');
		$arr_section_id = $this->cms_function->extract_records($arr_section, 'id');

		$arr_image_lookup = array();

		if (count($arr_section_id) > 0)
		{
			$this->db->where_in('section_id', $arr_section_id);
			$arr_image = $this->core_model->get('image');

			foreach ($arr_image as $image)
			{
				$arr_image_lookup[$image->section_id] = ($image->name != '') ? $image->name : $image->id . '.' . $image->ext;
			}
		}

		foreach ($arr_section as $section)
		{
			$section->image_name = (isset($arr_image_lookup[$section->id])) ? $arr_image_lookup[$section->id] : '';
		}

		if ($header_id > 0)
		{
			$this->db->where('header_id', $header_id);
		}

		$count_section = $this->core_model->count('section');
		$count_page = ceil($count_section / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Section-' . $header_id;
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['arr_section'] = $arr_section;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['header'] = $header;

		$this->load->view('html', $arr_data);
		$this->load->view('section', $arr_data);
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
				throw new Exception('You have no access to add section. Please contact your administrator.');
			}

			$section_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$section_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$section_record = $this->cms_function->populate_foreign_field($section_record['category_id'], $section_record, 'category');

			$this->_validate_add($section_record);

			$section_id = $this->core_model->insert('section', $section_record);
			$section_record['id'] = $section_id;
			$section_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($section_record['id'], 'add', $section_record, array(), 'section');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('section_id' => $section_id));
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

	public function ajax_change_status($section_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($section_id <= 0)
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
				throw new Exception('You have no access to edit section. Please contact your administrator.');
			}

			$old_section = $this->core_model->get('section', $section_id);

			$old_section_record = array();

			foreach ($old_section as $key => $value)
			{
				$old_section_record[$key] = $value;
			}

			$section_record = array();

			foreach ($_POST as $k => $v)
			{
				$section_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('section', $section_id, $section_record);
			$section_record['id'] = $section_id;
			$section_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $section_record, $old_section_record, 'section');


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

	public function ajax_delete($section_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($section_id <= 0)
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
				throw new Exception('You have no access to delete section. Please contact your administrator.');
			}

			$section = $this->core_model->get('section', $section_id);
			$updated = $_POST['updated'];
			$section_record = array();

			foreach ($section as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another section. Please refresh the page.');
				}
				else
				{
					$section_record[$k] = $v;
				}
			}

			$this->_validate_delete($section_id);

			$this->core_model->delete('section', $section_id);
			$section_record['id'] = $section->id;
			$section_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($section_record['id'], 'delete', $section_record, array(), 'section');

			if ($this->_has_image > 0)
			{
				$this->db->where('section_id', $section_id);
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

	public function ajax_edit($section_id)
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
				throw new Exception('You have no access to edit section. Please contact your administrator.');
			}

			$old_section = $this->core_model->get('section', $section_id);

			$old_section_record = array();

			foreach ($old_section as $key => $value)
			{
				$old_section_record[$key] = $value;
			}

			$section_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated' && $v != $old_user_record[$k])
				{
					throw new Exception('This data has been updated by another user. Please refresh the page.');
				}
				elseif ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$section_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($section_id, $section_record);

			$this->core_model->update('section', $section_id, $section_record);
			$section_record['id'] = $section_id;
			$section_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($section_record['id'], 'edit', $section_record, $old_section_record, 'section');

			if ($image_id > 0)
            {
                $this->db->where('section_id', $section_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('section_id' => $section_id));
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

	public function ajax_get($section_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($section_id <= 0)
			{
				throw new Exception();
			}

			$section = $this->core_model->get('section', $section_id);

			$json['section'] = $section;
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




	private function _get_category()
	{
		$this->db->select('id, type, name');
		$this->db->order_by('type, name');
		return $this->core_model->get('category');
	}

	private function _validate_add($section_record)
	{
		$this->db->where('name', $section_record['name']);
		$count_user = $this->core_model->count('section');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($section_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $section_id);
		$count_user = $this->core_model->count('section');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($section_id, $section_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $section_id);
		$count_user = $this->core_model->count('section');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}
	}
}