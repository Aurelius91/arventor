<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upgrade_1_0_0_to_1_0_1 extends CI_Controller
{
	private $_setting;

	public function __construct()
	{
		parent:: __construct();

		$this->_setting = $this->setting_model->load();
	}




	public function index()
	{
		set_time_limit(0);

		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($this->_setting->system_version != '1.0.0')
			{
				return;
			}

			$this->_update();
			$this->_upgrade();
			$this->_patch();
			$this->_patch_user_access();
			$this->_alter_index();

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




	private function _alter_index()
	{
		$query = mysql_query("SELECT DATABASE();");
		$row = mysql_fetch_row($query);

		$database = $row[0];

		$this->db->simple_query("USE `{$database}`");

		$query = $this->db->query('SHOW TABLES');

		foreach ($query->result_array() as $arr_table)
		{
			$table = $arr_table["Tables_in_{$database}"];

			$query2 = $this->db->query("SHOW INDEX FROM `{$table}`");

			$arr_old_index = array();

			foreach ($query2->result_array() as $arr_index)
			{
				if ($arr_index['Key_name'] == 'PRIMARY')
				{
					continue;
				}

				$arr_old_index[][$arr_index['Column_name']] = $arr_index['Key_name'];
			}

			$arr_field = $this->db->field_data($table);

			$arr_new_index = array();

			foreach ($arr_field as $field)
			{
				if (!(preg_match('/_id$/', $field->name) && $field->type == 'int' && $field->max_length == 10) && !(preg_match('/date$/', $field->name)) && $field->name != 'module_id')
				{
					continue;
				}

				$arr_new_index[] = $field->name;
			}

			foreach ($arr_new_index as $key => $new_index)
			{
				if (!(isset($arr_old_index[$key][$new_index]) && $arr_old_index[$key][$new_index] == $new_index))
				{
					break;
				}

				unset($arr_old_index[$key][$new_index]);
				unset($arr_new_index[$key]);
			}

			foreach ($arr_old_index as $old_index)
			{
				foreach ($old_index as $index)
				{
					$this->db->simple_query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
				}
			}

			foreach ($arr_new_index as $new_index)
			{
				$this->db->simple_query("ALTER TABLE `{$table}` ADD INDEX `{$new_index}` (`{$new_index}`)");
			}
		}
	}

	private function _patch()
	{
	}

	private function _patch_user_access()
	{
		$arr_user_id = array(1, 2);

		$this->db->where_in('user_id', $arr_user_id);
		$this->core_model->delete('user_access');

		$this->db->select('id');
		$arr_user = $this->core_model->get('user', $arr_user_id);

		$this->db->select('id, add, delete, edit, list');
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

				$this->core_model->insert('user_access', $user_access_record);
			}
		}
	}

	private function _update()
	{
		$this->db->simple_query("UPDATE `setting` SET `value` = '1.0.1' WHERE `name` = 'system_version';");
	}

	private function _upgrade()
	{
	}
}