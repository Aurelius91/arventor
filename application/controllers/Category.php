<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Category extends CI_Controller
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

		if (!isset($acl['category']) || $acl['category']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Category';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('category_add', $arr_data);
	}

	public function edit($category_id = 0)
	{
		$acl = $this->_acl;

		if ($category_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['category']) || $acl['category']->edit <= 0)
		{
			redirect(base_url());
		}

		$category = $this->core_model->get('category', $category_id);
		$category->address = $this->cms_function->trim_text($category->address);

		$this->db->select('module_id, add, delete, edit, list');
		$this->db->where('category_id', $category->id);
		$category->arr_category_access = $this->core_model->get('category_access');

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Category';
		$arr_data['category'] = $category;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('category_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['category']) || $acl['category']->list <= 0)
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
		$arr_category = $this->core_model->get('category');

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

		$count_category = $this->core_model->count('category');
		$count_page = ceil($count_category / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Category';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_category'] = $arr_category;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('category', $arr_data);
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

			if (!isset($acl['category']) || $acl['category']->add <= 0)
			{
				throw new Exception('You have no access to add category. Please contact your administrator.');
			}

			$category_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$category_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$category_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($category_record['name']));
            $category_record['url_name'] = preg_replace("/[\s_]/", "-", $category_record['url_name']);

			$this->_validate_add($category_record);

			$category_id = $this->core_model->insert('category', $category_record);
			$category_record['id'] = $category_id;
			$category_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($category_record['id'], 'add', $category_record, array(), 'category');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('category_id' => $category_id));
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

	public function ajax_change_status($category_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($category_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['category']) || $acl['category']->edit <= 0)
			{
				throw new Exception('You have no access to edit category. Please contact your administrator.');
			}

			$old_category = $this->core_model->get('category', $category_id);

			$old_category_record = array();

			foreach ($old_category as $key => $value)
			{
				$old_category_record[$key] = $value;
			}

			$category_record = array();

			foreach ($_POST as $k => $v)
			{
				$category_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('category', $category_id, $category_record);
			$category_record['id'] = $category_id;
			$category_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $category_record, $old_category_record, 'category');

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

	public function ajax_delete($category_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($category_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['category']) || $acl['category']->delete <= 0)
			{
				throw new Exception('You have no access to delete category. Please contact your administrator.');
			}

			$category = $this->core_model->get('category', $category_id);
			$updated = $_POST['updated'];
			$category_record = array();

			foreach ($category as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another category. Please refresh the page.');
				}
				else
				{
					$category_record[$k] = $v;
				}
			}

			$this->_validate_delete($category_id);

			$this->core_model->delete('category', $category_id);
			$category_record['id'] = $category->id;
			$category_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($category_record['id'], 'delete', $category_record, array(), 'category');

			if ($this->_has_image > 0)
			{
				$this->db->where('category_id', $category_id);
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

	public function ajax_edit($category_id)
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

			if (!isset($acl['category']) || $acl['category']->edit <= 0)
			{
				throw new Exception('You have no access to edit category. Please contact your administrator.');
			}

			$old_category = $this->core_model->get('category', $category_id);

			$old_category_record = array();

			foreach ($old_category as $key => $value)
			{
				$old_category_record[$key] = $value;
			}

			$category_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_category_record[$k])
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
					$category_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$category_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($category_record['name']));
            $category_record['url_name'] = preg_replace("/[\s_]/", "-", $category_record['url_name']);

			$this->_validate_edit($category_id, $category_record);

			$this->core_model->update('category', $category_id, $category_record);
			$category_record['id'] = $category_id;
			$category_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($category_record['id'], 'edit', $category_record, $old_category_record, 'category');

			$this->cms_function->update_foreign_field(array('product'), $category_record, 'category');

			if ($image_id > 0)
            {
                $this->db->where('category_id', $category_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('category_id' => $category_id));
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

	public function ajax_get($category_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($category_id <= 0)
			{
				throw new Exception();
			}

			$category = $this->core_model->get('category', $category_id);

			$json['category'] = $category;
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




	private function _validate_add($category_record)
	{
		$this->db->where('name', $category_record['name']);
		$count_user = $this->core_model->count('category');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($category_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $category_id);
		$count_user = $this->core_model->count('category');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($category_id, $category_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $category_id);
		$count_user = $this->core_model->count('category');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $category_id);
		$this->db->where('name', $category_record['name']);
		$count_user = $this->core_model->count('category');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}