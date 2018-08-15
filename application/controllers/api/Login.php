<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller
{
	private $_setting;

	public function __construct()
	{
		parent:: __construct();

		$this->_setting = $this->setting_model->load();
	}




	public function ajax_login()
	{
		$json['status'] = 'success';

		try
		{
			$this->load->helper('security');

			$username = $this->security->xss_clean($this->input->post('username'));
			$password = md5($this->security->xss_clean($this->input->post('password')));

			$json['username'] = $this->input->get('username');
			$json['password'] = $this->input->get('password');

			$json['username2'] = $this->input->post('username');
			$json['password2'] = $this->input->post('password');

			$json['post'] = $_POST;
			$json['get'] = $_GET;
			$json['request'] = $_REQUEST;

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