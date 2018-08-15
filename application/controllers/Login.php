<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller
{
	private $_setting;

	public function __construct()
	{
		parent:: __construct();

		$this->_setting = $this->setting_model->load();
	}




	public function index()
	{
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['setting'] = $this->_setting;

		$this->load->view('login', $arr_data);
	}




	public function ajax_login()
	{
		$json['status'] = 'success';

		try
		{
			$this->load->helper('security');

			$username = $this->security->xss_clean($this->input->post('username'));
			$password = md5($this->security->xss_clean($this->input->post('password')));

			$this->db->select('id, type, name');
			$this->db->where("BINARY username = '{$username}'", null, false);
			$this->db->where("BINARY password = '{$password}'", null, false);
			$this->db->where('active >', 0);
			$arr_user = $this->core_model->get('user');

			if (count($arr_user) > 0)
			{
				if ($this->_setting->system_memory_quota > 0)
				{
					// get database size
					$query = $this->db->query("SHOW TABLE STATUS");
					$size = 0;

					foreach ($query->result() as $row)
					{
						$size += $row->Data_length + $row->Index_length;
					}

					$database_size = number_format($size / (1024 * 1024), 3);

					// get image upload size
					$files = scandir('images/website');

					$filesize = 0;

					foreach ($files as $file)
					{
						if ($file == '.' || $file == '..')
						{
							continue;
						}

						$filesize += filesize('images/website/' . $file);
					}

					$image_size = number_format($filesize / (1024 * 1024), 3);
					$total_size = number_format($image_size + $database_size, 2);

					if ($arr_user[0]->id > 1 && ($total_size > $this->_setting->system_memory_quota))
					{
						throw new Exception('You have reach your quota limit. Please contact our Customer Service for more information.');
					}
				}

				$this->session->set_userdata('user_id', $arr_user[0]->id);
				$this->session->set_userdata('user_name', $arr_user[0]->name);
			}
			else
			{
				throw new Exception('Wrong Username or Password.');
			}

			$json['user'] = $arr_user[0];
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

	public function ajax_login_app()
	{
		$json['status'] = 'success';

		try
		{
			$this->load->helper('security');

			$username = $this->security->xss_clean($this->input->get('username'));
			$password = md5($this->security->xss_clean($this->input->get('password')));

			$this->db->where("BINARY username = '{$username}'", null, false);
			$this->db->where("BINARY password = '{$password}'", null, false);
			$this->db->where('active >', 0);
			$arr_user = $this->core_model->get('user');

			if (count($arr_user) <= 0)
			{
				throw new Exception('Wrong Username or Password.');
			}

			$json['user'] = $arr_user[0];
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
}