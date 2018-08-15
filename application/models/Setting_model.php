<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Setting_model extends CI_Model
{
	function get($name = '')
	{
		$this->db->select('value');
		$this->db->where('name', $name);

		$query = $this->db->get('setting');
		$row = $query->first_row();

		return ($row) ? $row->value : '';
	}

	function load()
	{
		$this->db->select('name, value');

		$query = $this->db->get('setting');

		$records = $query->result();

		$setting = new stdClass();

		foreach ($records as $record)
		{
			$setting->{$record->name} = $record->value;
		}

		return $setting;
	}

	function set($name = '', $value = '')
	{
		$this->db->where('name', $name);

		return $this->db->update('setting', array('value' => $value));
	}
}
