<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class transfer extends CI_Controller
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

		if (!isset($acl['transfer']) || $acl['transfer']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'transfer';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('transfer_add', $arr_data);
	}

	public function edit($transfer_id = 0)
	{
		$acl = $this->_acl;

		if ($transfer_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['transfer']) || $acl['transfer']->edit <= 0)
		{
			redirect(base_url());
		}

		$transfer = $this->core_model->get('transfer', $transfer_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'transfer';
		$arr_data['transfer'] = $transfer;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('transfer_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['transfer']) || $acl['transfer']->list <= 0)
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
		$arr_transfer = $this->core_model->get('transfer');
		$arr_transfer_id = $this->cms_function->extract_records($arr_transfer, 'id');

		foreach ($arr_transfer as $transfer)
		{
			$transfer->amount_display = number_format($transfer->amount, 0, ',', '.');
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

		$count_transfer = $this->core_model->count('transfer');
		$count_page = ceil($count_transfer / $this->_setting->setting__limit_page);

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'transfer';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_transfer'] = $arr_transfer;
		$arr_data['date_display'] = $date_display;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('transfer', $arr_data);
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

			if (!isset($acl['transfer']) || $acl['transfer']->add <= 0)
			{
				throw new Exception('You have no access to add transfer. Please contact your administrator.');
			}

			$transfer_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$transfer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$transfer_record = $this->cms_function->populate_foreign_field($transfer_record['statement_id'], $transfer_record, 'statement');

			$statement_to = $this->core_model->get('statement', $transfer_record['statement_to_id']);
			$transfer_record['statement_to_type'] = $statement_to->type;
			$transfer_record['statement_to_number'] = $statement_to->number;
			$transfer_record['statement_to_name'] = $statement_to->name;
			$transfer_record['statement_to_date'] = $statement_to->date;
			$transfer_record['statement_to_status'] = $statement_to->status;

			$this->_validate_add($transfer_record);

			$transfer_id = $this->core_model->insert('transfer', $transfer_record);
			$transfer_record['id'] = $transfer_id;
			$transfer_record['last_query'] = $this->db->last_query();

			if (!isset($transfer_record['number']) || (isset($transfer_record['number']) && $transfer_record['number'] == ''))
			{
				$transfer_record['number'] = '#TRF' . str_pad($transfer_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('transfer', $transfer_id, array('number' => $transfer_record['number']));
			}

			$transfer_record['name'] = $transfer_record['number'];

			$this->cms_function->system_log($transfer_record['id'], 'add', $transfer_record, array(), 'transfer');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('transfer_id' => $transfer_id));
			}

			// add Transaction out
			$transaction_record = array();
			$transaction_record['transfer_id'] = $transfer_id;
			$transaction_record['statement_id'] = $transfer_record['statement_id'];

			$transaction_record['date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['credit'] = $transfer_record['amount'];

			$transaction_record['transfer_type'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';
			$transaction_record['transfer_number'] = (isset($transfer_record['number'])) ? $transfer_record['number'] : '';
			$transaction_record['transfer_name'] = (isset($transfer_record['name'])) ? $transfer_record['name'] : '';
			$transaction_record['transfer_date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['transfer_status'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';

			$transaction_record['statement_type'] = (isset($transfer_record['statement_type'])) ? $transfer_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($transfer_record['statement_number'])) ? $transfer_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($transfer_record['statement_name'])) ? $transfer_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($transfer_record['statement_date'])) ? $transfer_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($transfer_record['statement_type'])) ? $transfer_record['statement_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

			// add transaction in
			$transaction_record = array();
			$transaction_record['transfer_id'] = $transfer_id;
			$transaction_record['statement_id'] = $transfer_record['statement_to_id'];

			$transaction_record['date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['debit'] = $transfer_record['amount'];

			$transaction_record['transfer_type'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';
			$transaction_record['transfer_number'] = (isset($transfer_record['number'])) ? $transfer_record['number'] : '';
			$transaction_record['transfer_name'] = (isset($transfer_record['name'])) ? $transfer_record['name'] : '';
			$transaction_record['transfer_date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['transfer_status'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';

			$transaction_record['statement_type'] = (isset($transfer_record['statement_to_type'])) ? $transfer_record['statement_to_type'] : '';
			$transaction_record['statement_number'] = (isset($transfer_record['statement_to_number'])) ? $transfer_record['statement_to_number'] : '';
			$transaction_record['statement_name'] = (isset($transfer_record['statement_to_name'])) ? $transfer_record['statement_to_name'] : '';
			$transaction_record['statement_date'] = (isset($transfer_record['statement_to_date'])) ? $transfer_record['statement_to_date'] : '';
			$transaction_record['statement_status'] = (isset($transfer_record['statement_to_type'])) ? $transfer_record['statement_to_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

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

	public function ajax_change_status($transfer_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($transfer_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['transfer']) || $acl['transfer']->edit <= 0)
			{
				throw new Exception('You have no access to edit transfer. Please contact your administrator.');
			}

			$old_transfer = $this->core_model->get('transfer', $transfer_id);

			$old_transfer_record = array();

			foreach ($old_transfer as $key => $value)
			{
				$old_transfer_record[$key] = $value;
			}

			$transfer_record = array();

			foreach ($_POST as $k => $v)
			{
				$transfer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('transfer', $transfer_id, $transfer_record);
			$transfer_record['id'] = $transfer_id;
			$transfer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $transfer_record, $old_transfer_record, 'transfer');

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

	public function ajax_delete($transfer_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($transfer_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['transfer']) || $acl['transfer']->delete <= 0)
			{
				throw new Exception('You have no access to delete transfer. Please contact your administrator.');
			}

			$transfer = $this->core_model->get('transfer', $transfer_id);
			$updated = $_POST['updated'];
			$transfer_record = array();

			foreach ($transfer as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another transfer. Please refresh the page.');
				}
				else
				{
					$transfer_record[$k] = $v;
				}
			}

			$this->_validate_delete($transfer_id);

			$this->core_model->delete('transfer', $transfer_id);
			$transfer_record['id'] = $transfer->id;
			$transfer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($transfer_record['id'], 'delete', $transfer_record, array(), 'transfer');

			if ($this->_has_image > 0)
			{
				$this->db->where('transfer_id', $transfer_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			// delete old_transaction
            $this->db->where('transfer_id', $transfer_id);
            $this->core_model->delete('transaction');

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

	public function ajax_edit($transfer_id)
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

			if (!isset($acl['transfer']) || $acl['transfer']->edit <= 0)
			{
				throw new Exception('You have no access to edit transfer. Please contact your administrator.');
			}

			$old_transfer = $this->core_model->get('transfer', $transfer_id);

			$old_transfer_record = array();

			foreach ($old_transfer as $key => $value)
			{
				$old_transfer_record[$key] = $value;
			}

			$transfer_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_transfer_record[$k])
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
					$transfer_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$transfer_record = $this->cms_function->populate_foreign_field($transfer_record['statement_id'], $transfer_record, 'statement');

			$statement_to = $this->core_model->get('statement', $transfer_record['statement_to_id']);
			$transfer_record['statement_to_type'] = $statement_to->type;
			$transfer_record['statement_to_number'] = $statement_to->number;
			$transfer_record['statement_to_name'] = $statement_to->name;
			$transfer_record['statement_to_date'] = $statement_to->date;
			$transfer_record['statement_to_status'] = $statement_to->status;

			$this->_validate_edit($transfer_id, $transfer_record);

			$this->core_model->update('transfer', $transfer_id, $transfer_record);
			$transfer_record['id'] = $transfer_id;
			$transfer_record['name'] = $transfer_record['number'];
			$transfer_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($transfer_record['id'], 'edit', $transfer_record, $old_transfer_record, 'transfer');

			if ($image_id > 0)
            {
                $this->db->where('transfer_id', $transfer_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('transfer_id' => $transfer_id));
            }

            // delete old_transaction
            $this->db->where('transfer_id', $transfer_id);
            $this->core_model->delete('transaction');

			// add Transaction out
			$transaction_record = array();
			$transaction_record['transfer_id'] = $transfer_id;
			$transaction_record['statement_id'] = $transfer_record['statement_id'];

			$transaction_record['date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['credit'] = $transfer_record['amount'];

			$transaction_record['transfer_type'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';
			$transaction_record['transfer_number'] = (isset($transfer_record['number'])) ? $transfer_record['number'] : '';
			$transaction_record['transfer_name'] = (isset($transfer_record['name'])) ? $transfer_record['name'] : '';
			$transaction_record['transfer_date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['transfer_status'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';

			$transaction_record['statement_type'] = (isset($transfer_record['statement_type'])) ? $transfer_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($transfer_record['statement_number'])) ? $transfer_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($transfer_record['statement_name'])) ? $transfer_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($transfer_record['statement_date'])) ? $transfer_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($transfer_record['statement_type'])) ? $transfer_record['statement_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

			// add transaction in
			$transaction_record = array();
			$transaction_record['transfer_id'] = $transfer_id;
			$transaction_record['statement_id'] = $transfer_record['statement_to_id'];

			$transaction_record['date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['debit'] = $transfer_record['amount'];

			$transaction_record['transfer_type'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';
			$transaction_record['transfer_number'] = (isset($transfer_record['number'])) ? $transfer_record['number'] : '';
			$transaction_record['transfer_name'] = (isset($transfer_record['name'])) ? $transfer_record['name'] : '';
			$transaction_record['transfer_date'] = (isset($transfer_record['date'])) ? $transfer_record['date'] : '';
			$transaction_record['transfer_status'] = (isset($transfer_record['type'])) ? $transfer_record['type'] : '';

			$transaction_record['statement_type'] = (isset($transfer_record['statement_to_type'])) ? $transfer_record['statement_to_type'] : '';
			$transaction_record['statement_number'] = (isset($transfer_record['statement_to_number'])) ? $transfer_record['statement_to_number'] : '';
			$transaction_record['statement_name'] = (isset($transfer_record['statement_to_name'])) ? $transfer_record['statement_to_name'] : '';
			$transaction_record['statement_date'] = (isset($transfer_record['statement_to_date'])) ? $transfer_record['statement_to_date'] : '';
			$transaction_record['statement_status'] = (isset($transfer_record['statement_to_type'])) ? $transfer_record['statement_to_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

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

	public function ajax_get($transfer_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($transfer_id <= 0)
			{
				throw new Exception();
			}

			$transfer = $this->core_model->get('transfer', $transfer_id);
			$transfer->date_display = date('Y-m-d', $transfer->date);
			$transfer->amount_display = number_format($transfer->amount, 0, '', '');

			$json['transfer'] = $transfer;
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

	private function _validate_add($transfer_record)
	{
	}

	private function _validate_delete($transfer_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $transfer_id);
		$count_user = $this->core_model->count('transfer');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($transfer_id, $transfer_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $transfer_id);
		$count_user = $this->core_model->count('transfer');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}
	}
}