<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Core_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}




	public function count($table)
	{
		return $this->db->count_all_results($table);
	}

	public function delete($table, $id = 0)
	{
		if (is_array($id) && count($id) > 0)
		{
			$this->db->where_in('id', $id);
		}
		elseif (!is_array($id) && $id > 0)
		{
			$this->db->where('id', $id);
		}

		// if (count($this->db->ar_where) <= 0)
		// {
		// 	return FALSE;
		// }

		return $this->db->delete($table);
	}

	public function get($table, $id = 0)
	{
		if (is_array($id))
		{
			if (count($id) <= 0)
			{
				$this->db->ar_select = array();
				$this->db->ar_from = array();
				$this->db->ar_join = array();
				$this->db->ar_where = array();
				$this->db->ar_like = array();
				$this->db->ar_groupby = array();
				$this->db->ar_having = array();
				$this->db->ar_orderby = array();
				$this->db->ar_wherein = array();
				$this->db->ar_aliased_tables = array();
				$this->db->ar_no_escape = array();
				$this->db->ar_distinct = FALSE;
				$this->db->ar_limit = FALSE;
				$this->db->ar_offset = FALSE;
				$this->db->ar_order = FALSE;

				return array();
			}

			$this->db->where_in('id', $id);
		}
		elseif ($id > 0)
		{
			$this->db->where('id', $id);
		}

		$query = $this->db->get($table);

		if (!is_array($id) && $id > 0)
		{
			return $query->first_row();
		}

		return $query->result();
	}

	public function insert($table, $record = array())
	{
		$user_id = $this->session->userdata('user_id');
		$user_name = $this->session->userdata('user_name');

		$record['created'] = NULL;
		$record['author_id'] = ($user_id) ? $user_id : 0;
		$record['author_name'] = ($user_name) ? $user_name : '';

		if (!$this->db->insert($table, $record))
		{
			return FALSE;
		}

		return $this->db->insert_id();
	}

	public function update($table, $id = 0, $record = array())
	{
		if (is_array($id) && count($id) > 0)
		{
			$this->db->where_in('id', $id);
		}
		elseif (!is_array($id) && $id > 0)
		{
			$this->db->where('id', $id);
		}

		// if (count($this->db->ar_where) <= 0)
		// {
		// 	return FALSE;
		// }

		return $this->db->update($table, $record);
	}
}
