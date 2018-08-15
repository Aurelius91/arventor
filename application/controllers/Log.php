<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log extends CI_Controller
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




	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['system_log']) || $acl['system_log']->list <= 0)
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
			$this->db->or_like('date', $query);
			$this->db->or_like('author_name', $query);
		}
		else
		{
			if ($filter == 'user')
			{
				$this->db->like('author_name', $query);
			}
			elseif ($filter == 'action')
			{
				$this->db->like('name', $query);
			}
			else
			{
				$this->db->like($filter, $query);
			}
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("date DESC");
		$arr_log = $this->core_model->get('log');
		$arr_user_id = $this->cms_function->extract_records($arr_log, 'author_id');

		$arr_user_lookup = array();

		$this->db->select('id, position');
		$arr_user = $this->core_model->get('user', $arr_user_id);

		foreach ($arr_user as $user)
		{
			$arr_user_lookup[$user->id] = clone $user;
		}

		foreach ($arr_log as $log)
		{
			$log->date_display = ($log->date > 0) ? date('d F Y H:i:s', $log->date) : '';
			$log->author_position = (isset($arr_user_lookup[$log->author_id])) ? $arr_user_lookup[$log->author_id]->position : '';
		}

		if ($filter == 'all')
		{
			$this->db->like('name', $query);
			$this->db->or_like('date', $query);
			$this->db->or_like('author_name', $query);
		}
		else
		{
			if ($filter == 'user')
			{
				$this->db->like('author_name', $query);
			}
			elseif ($filter == 'action')
			{
				$this->db->like('name', $query);
			}
			else
			{
				$this->db->like($filter, $query);
			}
		}

		$count_log = $this->core_model->count('log');
		$count_page = ceil($count_log / $this->_setting->setting__limit_page);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Log';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['filter'] = $filter;
		$arr_data['query'] = $query;
		$arr_data['arr_log'] = $arr_log;
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('log', $arr_data);
	}
}