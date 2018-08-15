<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logout extends CI_Controller
{
	public function __construct()
	{
		parent:: __construct();
	}




	public function index()
	{
		$this->session->set_userdata('user_id', NULL);
		$this->session->sess_destroy();

		redirect(base_url() . 'login/');
	}
}