<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Generate extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
	}




	public function alter_index()
	{
		set_time_limit(0);

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

	public function views()
	{
		$arr_file = scandir(FCPATH . 'application/v');

		foreach ($arr_file as $file)
		{
			if ($file == '.' || $file == '..')
			{
				continue;
			}

			$print = file_get_contents(FCPATH . "application/v/{$file}");

			if ($print !== FALSE)
			{
				$print = str_replace("\n", "", $print);
				$print = str_replace("\t", "", $print);
				$print = str_replace("\r", "", $print);
				$print = preg_replace('/\s\s+/', ' ', trim($print));
				$print = str_replace('<?php', '<?php ', $print);

				$filename = "application/views/{$file}";

				$filehandle = fopen($filename, 'w') or die("can't open file");

				fwrite($filehandle, $print);

				fclose($filehandle);
			}
		}
	}
}
