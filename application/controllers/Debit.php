<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Debit extends CI_Controller
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




	public function add()
	{
		$acl = $this->_acl;

		if (!isset($acl['debit']) || $acl['debit']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Debit';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('debit_add', $arr_data);
	}

	public function edit($debit_id = 0)
	{
		$acl = $this->_acl;

		if ($debit_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['debit']) || $acl['debit']->edit <= 0)
		{
			redirect(base_url());
		}

		$debit = $this->core_model->get('debit', $debit_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Debit';
		$arr_data['debit'] = $debit;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('debit_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['debit']) || $acl['debit']->list <= 0)
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
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$this->db->limit($this->_setting->setting__limit_page, ($page - 1) * $this->_setting->setting__limit_page);
		$this->db->order_by("name");
		$arr_debit = $this->core_model->get('debit');
		$arr_debit_id = $this->cms_function->extract_records($arr_debit, 'id');

		foreach ($arr_debit as $debit)
		{
			$debit->amount_display = number_format($debit->amount, 0, ',', '.');
		}

		if ($query != '')
		{
			$this->db->like('name', $query);
		}

		if ($filter == 'all')
		{
			$this->db->like('name', $query);
		}
		else
		{
			$this->db->like($filter, $query);
		}

		$count_debit = $this->core_model->count('debit');
		$count_page = ceil($count_debit / $this->_setting->setting__limit_page);

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Debit';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_debit'] = $arr_debit;
		$arr_data['date_display'] = $date_display;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('debit', $arr_data);
	}




	public function ajax_add()
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['debit']) || $acl['debit']->add <= 0)
			{
				throw new Exception('You have no access to add debit. Please contact your administrator.');
			}

			$debit_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$debit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$debit_record = $this->cms_function->populate_foreign_field($debit_record['statement_id'], $debit_record, 'statement');

			$this->_validate_add($debit_record);

			$debit_id = $this->core_model->insert('debit', $debit_record);
			$debit_record['id'] = $debit_id;
			$debit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($debit_record['id'], 'add', $debit_record, array(), 'debit');

			if (!isset($debit_record['number']) || (isset($debit_record['number']) && $debit_record['number'] == ''))
			{
				$debit_record['number'] = '#DB' . str_pad($debit_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('debit', $debit_id, array('number' => $debit_record['number']));
			}

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('debit_id' => $debit_id));
			}

			$transaction_record = array();
			$transaction_record['debit_id'] = $debit_id;
			$transaction_record['statement_id'] = $debit_record['statement_id'];

			$transaction_record['date'] = (isset($debit_record['date'])) ? $debit_record['date'] : '';
			$transaction_record['debit'] = $debit_record['amount'];

			$transaction_record['debit_type'] = (isset($debit_record['type'])) ? $debit_record['type'] : '';
			$transaction_record['debit_number'] = (isset($debit_record['number'])) ? $debit_record['number'] : '';
			$transaction_record['debit_name'] = (isset($debit_record['name'])) ? $debit_record['name'] : '';
			$transaction_record['debit_date'] = (isset($debit_record['date'])) ? $debit_record['date'] : '';
			$transaction_record['debit_status'] = (isset($debit_record['type'])) ? $debit_record['type'] : '';

			$transaction_record['statement_type'] = (isset($debit_record['statement_type'])) ? $debit_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($debit_record['statement_number'])) ? $debit_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($debit_record['statement_name'])) ? $debit_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($debit_record['statement_date'])) ? $debit_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($debit_record['statement_type'])) ? $debit_record['statement_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

			// update transaction_id in statement
			$this->core_model->update('debit', $debit_id, array(
				'transaction_id' => $transaction_id,
				'transaction_date' => $transaction_record['debit_date']
			));

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

	public function ajax_change_status($debit_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($debit_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['debit']) || $acl['debit']->edit <= 0)
			{
				throw new Exception('You have no access to edit debit. Please contact your administrator.');
			}

			$old_debit = $this->core_model->get('debit', $debit_id);

			$old_debit_record = array();

			foreach ($old_debit as $key => $value)
			{
				$old_debit_record[$key] = $value;
			}

			$debit_record = array();

			foreach ($_POST as $k => $v)
			{
				$debit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('debit', $debit_id, $debit_record);
			$debit_record['id'] = $debit_id;
			$debit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $debit_record, $old_debit_record, 'debit');

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

	public function ajax_delete($debit_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($debit_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['debit']) || $acl['debit']->delete <= 0)
			{
				throw new Exception('You have no access to delete debit. Please contact your administrator.');
			}

			$debit = $this->core_model->get('debit', $debit_id);
			$updated = $_POST['updated'];
			$debit_record = array();

			foreach ($debit as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another debit. Please refresh the page.');
				}
				else
				{
					$debit_record[$k] = $v;
				}
			}

			$this->_validate_delete($debit_id);

			$this->core_model->delete('debit', $debit_id);
			$debit_record['id'] = $debit->id;
			$debit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($debit_record['id'], 'delete', $debit_record, array(), 'debit');

			if ($this->_has_image > 0)
			{
				$this->db->where('debit_id', $debit_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->core_model->delete('transaction', $debit_record['transaction_id']);

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

	public function ajax_edit($debit_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['debit']) || $acl['debit']->edit <= 0)
			{
				throw new Exception('You have no access to edit debit. Please contact your administrator.');
			}

			$old_debit = $this->core_model->get('debit', $debit_id);

			$old_debit_record = array();

			foreach ($old_debit as $key => $value)
			{
				$old_debit_record[$key] = $value;
			}

			$debit_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_debit_record[$k])
					{
						throw new Exception('This data has been updated by another user. Please refresh the page.');
					}
				}
				elseif ($k == 'image_id')
                {
                    $image_id = $v;
                }
				else
				{
					$debit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$debit_record = $this->cms_function->populate_foreign_field($debit_record['statement_id'], $debit_record, 'statement');

			$this->_validate_edit($debit_id, $debit_record);

			$this->core_model->update('debit', $debit_id, $debit_record);
			$debit_record['id'] = $debit_id;
			$debit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($debit_record['id'], 'edit', $debit_record, $old_debit_record, 'debit');

			if ($image_id > 0)
            {
                $this->db->where('debit_id', $debit_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('debit_id' => $debit_id));
            }

            $transaction_record = array();

			$transaction_record = array();
			$transaction_record['statement_id'] = $debit_record['statement_id'];

			$transaction_record['date'] = (isset($debit_record['date'])) ? $debit_record['date'] : '';
			$transaction_record['debit'] = $debit_record['amount'];

			$transaction_record['debit_type'] = (isset($debit_record['type'])) ? $debit_record['type'] : '';
			$transaction_record['debit_number'] = (isset($debit_record['number'])) ? $debit_record['number'] : '';
			$transaction_record['debit_name'] = (isset($debit_record['name'])) ? $debit_record['name'] : '';
			$transaction_record['debit_date'] = (isset($debit_record['date'])) ? $debit_record['date'] : '';
			$transaction_record['debit_status'] = (isset($debit_record['type'])) ? $debit_record['type'] : '';

			$transaction_record['statement_type'] = (isset($debit_record['statement_type'])) ? $debit_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($debit_record['statement_number'])) ? $debit_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($debit_record['statement_name'])) ? $debit_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($debit_record['statement_date'])) ? $debit_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($debit_record['statement_type'])) ? $debit_record['statement_type'] : '';

            $this->core_model->update('transaction', $old_debit_record['transaction_id'], $transaction_record);

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

	public function ajax_get($debit_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($debit_id <= 0)
			{
				throw new Exception();
			}

			$debit = $this->core_model->get('debit', $debit_id);
			$debit->date_display = date('Y-m-d', $debit->date);
			$debit->amount_display = number_format($debit->amount, 0, '', '');

			$json['debit'] = $debit;
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




	private function _get_statement()
	{
		$this->db->order_by('name');
		return $this->core_model->get('statement');
	}

	private function _validate_add($debit_record)
	{
		$this->db->where('name', $debit_record['name']);
		$count_user = $this->core_model->count('debit');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($debit_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $debit_id);
		$count_user = $this->core_model->count('debit');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($debit_id, $debit_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $debit_id);
		$count_user = $this->core_model->count('debit');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $debit_id);
		$this->db->where('name', $debit_record['name']);
		$count_user = $this->core_model->count('debit');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}