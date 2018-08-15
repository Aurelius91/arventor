<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Patch_user_access extends CI_Controller
{
	private $_setting;

	public function __construct()
	{
		parent:: __construct();

		$this->_setting = $this->setting_model->load();
	}




	public function generate()
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			$arr_user_id = array(1, 2);

			$this->db->where_in('user_id', $arr_user_id);
			$this->core_model->delete('user_access');

			$this->db->select('id');
			$arr_user = $this->core_model->get('user', $arr_user_id);

			$this->db->select('id, add, delete, edit, list');
			$this->db->where("{$this->_setting->system_product} >", 0);
			$arr_module = $this->core_model->get('module');

			foreach ($arr_user as $user)
			{
				foreach ($arr_module as $module)
				{
					unset($user_access_record);

					$user_access_record['user_id'] = $user->id;
					$user_access_record['module_id'] = $module->id;
					$user_access_record['add'] = $module->add;
					$user_access_record['delete'] = $module->delete;
					$user_access_record['edit'] = $module->edit;
					$user_access_record['list'] = $module->list;

					$user_access_record = $this->cms_function->populate_foreign_field($user->id, $user_access_record, 'user');
					$user_access_record = $this->cms_function->populate_foreign_field($module->id, $user_access_record, 'module');

					$this->core_model->insert('user_access', $user_access_record);
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
}