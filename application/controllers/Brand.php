<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class brand extends CI_Controller
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

		if (!isset($acl['brand']) || $acl['brand']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Brand';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('brand_add', $arr_data);
	}

	public function edit($brand_id = 0)
	{
		$acl = $this->_acl;

		if ($brand_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['brand']) || $acl['brand']->edit <= 0)
		{
			redirect(base_url());
		}

		$brand = $this->core_model->get('brand', $brand_id);
		$brand->address = $this->cms_function->trim_text($brand->address);

		$this->db->select('module_id, add, delete, edit, list');
		$this->db->where('brand_id', $brand->id);
		$brand->arr_brand_access = $this->core_model->get('brand_access');

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Brand';
		$arr_data['brand'] = $brand;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('brand_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['brand']) || $acl['brand']->list <= 0)
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
		$arr_brand = $this->core_model->get('brand');
		$arr_brand_id = $this->cms_function->extract_records($arr_brand, 'id');

		$arr_image_lookup = array();

		if (count($arr_brand_id) > 0)
		{
			$this->db->where_in('brand_id', $arr_brand_id);
			$arr_image = $this->core_model->get('image');

			foreach ($arr_image as $image)
			{
				$arr_image_lookup[$image->brand_id] = ($image->name != '') ? $image->name : $image->id . '.' . $image->ext;
			}
		}

		foreach ($arr_brand as $brand)
		{
			$brand->image_name = (isset($arr_image_lookup[$brand->id])) ? $arr_image_lookup[$brand->id] : '';
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

		$count_brand = $this->core_model->count('brand');
		$count_page = ceil($count_brand / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Brand';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_brand'] = $arr_brand;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('brand', $arr_data);
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

			if (!isset($acl['brand']) || $acl['brand']->add <= 0)
			{
				throw new Exception('You have no access to add brand. Please contact your administrator.');
			}

			$brand_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$brand_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$brand_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($brand_record['name']));
            $brand_record['url_name'] = preg_replace("/[\s_]/", "-", $brand_record['url_name']);

			$this->_validate_add($brand_record);

			$brand_id = $this->core_model->insert('brand', $brand_record);
			$brand_record['id'] = $brand_id;
			$brand_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($brand_record['id'], 'add', $brand_record, array(), 'brand');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('brand_id' => $brand_id));
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

	public function ajax_change_status($brand_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($brand_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['brand']) || $acl['brand']->edit <= 0)
			{
				throw new Exception('You have no access to edit brand. Please contact your administrator.');
			}

			$old_brand = $this->core_model->get('brand', $brand_id);

			$old_brand_record = array();

			foreach ($old_brand as $key => $value)
			{
				$old_brand_record[$key] = $value;
			}

			$brand_record = array();

			foreach ($_POST as $k => $v)
			{
				$brand_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('brand', $brand_id, $brand_record);
			$brand_record['id'] = $brand_id;
			$brand_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $brand_record, $old_brand_record, 'brand');

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

	public function ajax_delete($brand_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($brand_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['brand']) || $acl['brand']->delete <= 0)
			{
				throw new Exception('You have no access to delete brand. Please contact your administrator.');
			}

			$brand = $this->core_model->get('brand', $brand_id);
			$updated = $_POST['updated'];
			$brand_record = array();

			foreach ($brand as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another brand. Please refresh the page.');
				}
				else
				{
					$brand_record[$k] = $v;
				}
			}

			$this->_validate_delete($brand_id);

			$this->core_model->delete('brand', $brand_id);
			$brand_record['id'] = $brand->id;
			$brand_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($brand_record['id'], 'delete', $brand_record, array(), 'brand');

			if ($this->_has_image > 0)
			{
				$this->db->where('brand_id', $brand_id);
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

	public function ajax_edit($brand_id)
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

			if (!isset($acl['brand']) || $acl['brand']->edit <= 0)
			{
				throw new Exception('You have no access to edit brand. Please contact your administrator.');
			}

			$old_brand = $this->core_model->get('brand', $brand_id);

			$old_brand_record = array();

			foreach ($old_brand as $key => $value)
			{
				$old_brand_record[$key] = $value;
			}

			$brand_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_brand_record[$k])
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
					$brand_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$brand_record['url_name'] = str_replace(array('.', ',', '&', '?', '!', '/', '(', ')', '+'), '' , strtolower($brand_record['name']));
            $brand_record['url_name'] = preg_replace("/[\s_]/", "-", $brand_record['url_name']);

			$this->_validate_edit($brand_id, $brand_record);

			$this->core_model->update('brand', $brand_id, $brand_record);
			$brand_record['id'] = $brand_id;
			$brand_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($brand_record['id'], 'edit', $brand_record, $old_brand_record, 'brand');

			$this->cms_function->update_foreign_field(array('product'), $brand_record, 'brand');

			if ($image_id > 0)
            {
                $this->db->where('brand_id', $brand_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('brand_id' => $brand_id));
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

	public function ajax_get($brand_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($brand_id <= 0)
			{
				throw new Exception();
			}

			$brand = $this->core_model->get('brand', $brand_id);

			$json['brand'] = $brand;
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




	private function _validate_add($brand_record)
	{
		$this->db->where('name', $brand_record['name']);
		$count_user = $this->core_model->count('brand');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($brand_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $brand_id);
		$count_user = $this->core_model->count('brand');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($brand_id, $brand_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $brand_id);
		$count_user = $this->core_model->count('brand');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $brand_id);
		$this->db->where('name', $brand_record['name']);
		$count_user = $this->core_model->count('brand');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}