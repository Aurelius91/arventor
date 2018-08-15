<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cms_function
{
	public function check_memory()
	{
		$CI = &get_instance();

		$setting = $CI->setting_model->load();

		$total_size = 0;
		$available_size = 0;

		// get database size
		$query = $CI->db->query("SHOW TABLE STATUS");
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
		$total_size = $image_size + $database_size;
		$available_size = $setting->system_memory_quota - ($total_size);
		$total_size = number_format($total_size, 2);
		$available_size = number_format($available_size, 2);

		return $total_size;
	}

	public function extract_records($records, $field)
	{
		$data = array();

		foreach ($records as $record)
		{
			if (isset($data[$record->$field]))
			{
				continue;
			}

			$data[$record->$field] = $record->$field;
		}

		return array_values($data);
	}

	public function generate_acl($user_id)
	{
		if ($user_id <= 0)
		{
			return array();
		}

		$CI = &get_instance();
		$setting = $CI->setting_model->load();
		$acl = array();

		$CI->db->select('id');
		$CI->db->where($setting->system_product . ' >', 0);
		$arr_module = $CI->core_model->get('module');
		$arr_module_id = $CI->cms_function->extract_records($arr_module, 'id');

		if (count($arr_module_id) > 0)
		{
			$CI->db->where('user_id', $user_id);
			$CI->db->where_in('module_id', $arr_module_id);

			$arr_user_access = $CI->core_model->get('user_access');

			foreach ($arr_user_access as $user_access)
			{
				$acl[$user_access->module_id] = new stdClass();
				$acl[$user_access->module_id]->add = $user_access->add;
				$acl[$user_access->module_id]->delete = $user_access->delete;
				$acl[$user_access->module_id]->edit = $user_access->edit;
				$acl[$user_access->module_id]->list = $user_access->list;
			}
		}

		return $acl;
	}

	public function generate_csrf()
	{
		$CI = &get_instance();

		$arr_csrf = array();
		$arr_csrf['name'] = $CI->security->get_csrf_token_name();
		$arr_csrf['hash'] = $CI->security->get_csrf_hash();

		return $arr_csrf;
	}

	public function generate_image($type, $id, $query = '')
	{
		$CI = &get_instance();

		$CI->db->where("{$type}_id", $id);
		$CI->db->where('type', $query);
		$arr_image = $CI->core_model->get('image');

		$image_name = '';

		if (count($arr_image) > 0)
		{
			$image_name = ($arr_image[0]->name != '') ? $arr_image[0]->name : $arr_image[0]->id . '.' . $arr_image[0]->ext;
		}

		return $image_name;
	}

	public function generate_random_number()
	{
		$CI = &get_instance();

		$char = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$length = strlen($char);
		$number = '';

		for ($i = 0; $i < 6; $i++)
		{
			$number .= $char[rand(0, $length - 1)];
		}

		$CI->db->where('number', $number);
		$count_table = $CI->core_model->count('visitor');

		if ($count_table > 0)
		{
			$CI->cms_function->generate_random_number();
		}

		return $number;
	}

	public function populate_foreign_field($id, $record, $table)
	{
		$CI = &get_instance();

		if ($table == 'module')
		{
			$CI->db->select('type, number, name, date, status');
			$CI->db->where('id', $id);
			$arr_query_result = $CI->core_model->get($table);

			$query_result = (count($arr_query_result) > 0) ? $arr_query_result[0] : new stdClass();
		}
		else
		{
			if ($id > 0)
			{
				$CI->db->select('type, number, name, date, status');
				$query_result = $CI->core_model->get($table, $id);
			}
		}

		if ($id > 0)
		{
			foreach ($query_result as $k => $v)
			{
				$record["{$table}_{$k}"] = $v;
			}
		}

		return $record;
	}

	public function system_log($id, $type, $record, $old_record, $table)
	{
		$CI = &get_instance();

		$log_record = array();
		$log_record['date'] = time();
		$log_record['ref_id'] = $id;
		$log_record['type'] = ucwords($table);

		if ($type == 'add')
		{
			$log_record['name'] = (isset($record['name'])) ? 'Add ' . $table . ' ' . $record['name'] : 'Add ' . $table . ' ' . $record['title'];
		}
		elseif ($type == 'edit')
		{
			$log_record['name'] = (isset($record['name'])) ? 'Edit ' . $table . ' ' . $old_record['name'] . ' to ' .  $record['name'] : 'Edit ' . $table . ' ' . $old_record['title'] . ' to ' .  $record['title'];
		}
		elseif ($type == 'delete')
		{
			$log_record['name'] = (isset($record['name'])) ? 'Delete ' . $table . ' ' . $record['name'] : 'Delete ' . $table . ' ' . $record['title'];
		}
		elseif ($type == 'status')
		{
			if (isset($record['active']))
			{
				$status = ($record['active'] > 0) ? 'Active' : 'Inactive';
			}
			else
			{
				$status = $record['status'];
			}

			$log_record['name'] = (isset($old_record['name'])) ? 'Change Status ' . $old_record['name'] . ' to ' . $status : 'Change Status ' . $old_record['title'] . ' to ' . $status;
		}
		elseif ($type == 'Update Stock')
		{
			$log_record['name'] = 'Update Stock ' . $old_record['name'] . ' from ' . number_format($old_record['quantity'], 0, '', '') . ' to ' . $record['quantity'];
		}
		elseif ($type == 'setting')
		{
			$log_record['name'] = 'Update Setting';
		}

		$log_record['query'] = $record['last_query'];

		if ($type != 'delete' && $table != 'setting')
		{
			$CI->db->select('type, number, name, date, status');
			$query_result = $CI->core_model->get(strtolower($table), $id);

			foreach ($query_result as $k => $v)
			{
				$log_record["ref_{$k}"] = $v;
			}
		}

		$CI->core_model->insert('log', $log_record);
	}

	public function trim_text($text)
	{
		$text = str_replace("\n", "", $text);
		$text = str_replace("\t", "", $text);
		$text = str_replace("\r", "", $text);

		return $text;
	}

	public function update_foreign_field($arr_table, $record, $foreign_id)
	{
		$CI = &get_instance();

		foreach ($arr_table as $table)
		{
			$update_record["{$foreign_id}_type"] = (isset($record['type'])) ? $record['type'] : '';
			$update_record["{$foreign_id}_number"] = (isset($record['number'])) ? $record['number'] : '';
			$update_record["{$foreign_id}_name"] = (isset($record['name'])) ? $record['name'] : '';
			$update_record["{$foreign_id}_date"] = (isset($record['date'])) ? $record['date'] : 0;
			$update_record["{$foreign_id}_status"] = (isset($record['status'])) ? $record['status'] : '';

			$CI->db->where($foreign_id . '_id', $record['id']);
			$CI->core_model->update($table, 0, $update_record);

			if (($foreign_id == 'location' && ($table == 'movement' || $table == 'movement_item')) || $foreign_id == 'statement' && ($table == 'transfer' || $table == 'transfer_item'))
			{
				$update_record["{$foreign_id}_type"] = (isset($record['type'])) ? $record['type'] : '';
				$update_record["{$foreign_id}_number"] = (isset($record['number'])) ? $record['number'] : '';
				$update_record["{$foreign_id}_name"] = (isset($record['name'])) ? $record['name'] : '';
				$update_record["{$foreign_id}_date"] = (isset($record['date'])) ? $record['date'] : 0;
				$update_record["{$foreign_id}_status"] = (isset($record['status'])) ? $record['status'] : '';

				$CI->db->where($foreign_id . 'to_id', $record['id']);
				$CI->core_model->update($table, 0, $update_record);
			}
		}
	}
}
