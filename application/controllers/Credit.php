<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Credit extends CI_Controller
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

		if (!isset($acl['credit']) || $acl['credit']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Credit';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('credit_add', $arr_data);
	}

	public function edit($credit_id = 0)
	{
		$acl = $this->_acl;

		if ($credit_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['credit']) || $acl['credit']->edit <= 0)
		{
			redirect(base_url());
		}

		$credit = $this->core_model->get('credit', $credit_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Credit';
		$arr_data['credit'] = $credit;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('credit_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['credit']) || $acl['credit']->list <= 0)
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
		$arr_credit = $this->core_model->get('credit');
		$arr_credit_id = $this->cms_function->extract_records($arr_credit, 'id');

		foreach ($arr_credit as $credit)
		{
			$credit->amount_display = number_format($credit->amount, 0, ',', '.');
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

		$count_credit = $this->core_model->count('credit');
		$count_page = ceil($count_credit / $this->_setting->setting__limit_page);

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Credit';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_credit'] = $arr_credit;
		$arr_data['date_display'] = $date_display;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();
		$arr_data['arr_statement'] = $this->_get_statement();

		$this->load->view('html', $arr_data);
		$this->load->view('credit', $arr_data);
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

			if (!isset($acl['credit']) || $acl['credit']->add <= 0)
			{
				throw new Exception('You have no access to add credit. Please contact your administrator.');
			}

			$credit_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$credit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$credit_record = $this->cms_function->populate_foreign_field($credit_record['statement_id'], $credit_record, 'statement');

			$this->_validate_add($credit_record);

			$credit_id = $this->core_model->insert('credit', $credit_record);
			$credit_record['id'] = $credit_id;
			$credit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($credit_record['id'], 'add', $credit_record, array(), 'credit');

			if (!isset($credit_record['number']) || (isset($credit_record['number']) && $credit_record['number'] == ''))
			{
				$credit_record['number'] = '#CR' . str_pad($credit_id, 6, 0, STR_PAD_LEFT);
				$this->core_model->update('credit', $credit_id, array('number' => $credit_record['number']));
			}

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('credit_id' => $credit_id));
			}

			$transaction_record = array();
			$transaction_record['credit_id'] = $credit_id;
			$transaction_record['statement_id'] = $credit_record['statement_id'];

			$transaction_record['date'] = (isset($credit_record['date'])) ? $credit_record['date'] : '';
			$transaction_record['credit'] = $credit_record['amount'];

			$transaction_record['credit_type'] = (isset($credit_record['type'])) ? $credit_record['type'] : '';
			$transaction_record['credit_number'] = (isset($credit_record['number'])) ? $credit_record['number'] : '';
			$transaction_record['credit_name'] = (isset($credit_record['name'])) ? $credit_record['name'] : '';
			$transaction_record['credit_date'] = (isset($credit_record['date'])) ? $credit_record['date'] : '';
			$transaction_record['credit_status'] = (isset($credit_record['type'])) ? $credit_record['type'] : '';

			$transaction_record['statement_type'] = (isset($credit_record['statement_type'])) ? $credit_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($credit_record['statement_number'])) ? $credit_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($credit_record['statement_name'])) ? $credit_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($credit_record['statement_date'])) ? $credit_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($credit_record['statement_type'])) ? $credit_record['statement_type'] : '';
			$transaction_id = $this->core_model->insert('transaction', $transaction_record);

			// update transaction_id in statement
			$this->core_model->update('credit', $credit_id, array(
				'transaction_id' => $transaction_id,
				'transaction_date' => $transaction_record['credit_date']
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

	public function ajax_change_status($credit_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($credit_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['credit']) || $acl['credit']->edit <= 0)
			{
				throw new Exception('You have no access to edit credit. Please contact your administrator.');
			}

			$old_credit = $this->core_model->get('credit', $credit_id);

			$old_credit_record = array();

			foreach ($old_credit as $key => $value)
			{
				$old_credit_record[$key] = $value;
			}

			$credit_record = array();

			foreach ($_POST as $k => $v)
			{
				$credit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('credit', $credit_id, $credit_record);
			$credit_record['id'] = $credit_id;
			$credit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $credit_record, $old_credit_record, 'credit');

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

	public function ajax_delete($credit_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($credit_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['credit']) || $acl['credit']->delete <= 0)
			{
				throw new Exception('You have no access to delete credit. Please contact your administrator.');
			}

			$credit = $this->core_model->get('credit', $credit_id);
			$updated = $_POST['updated'];
			$credit_record = array();

			foreach ($credit as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another credit. Please refresh the page.');
				}
				else
				{
					$credit_record[$k] = $v;
				}
			}

			$this->_validate_delete($credit_id);

			$this->core_model->delete('credit', $credit_id);
			$credit_record['id'] = $credit->id;
			$credit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($credit_record['id'], 'delete', $credit_record, array(), 'credit');

			if ($this->_has_image > 0)
			{
				$this->db->where('credit_id', $credit_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->core_model->delete('transaction', $credit_record['transaction_id']);

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

	public function ajax_edit($credit_id)
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

			if (!isset($acl['credit']) || $acl['credit']->edit <= 0)
			{
				throw new Exception('You have no access to edit credit. Please contact your administrator.');
			}

			$old_credit = $this->core_model->get('credit', $credit_id);

			$old_credit_record = array();

			foreach ($old_credit as $key => $value)
			{
				$old_credit_record[$key] = $value;
			}

			$credit_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_credit_record[$k])
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
					$credit_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$credit_record = $this->cms_function->populate_foreign_field($credit_record['statement_id'], $credit_record, 'statement');

			$this->_validate_edit($credit_id, $credit_record);

			$this->core_model->update('credit', $credit_id, $credit_record);
			$credit_record['id'] = $credit_id;
			$credit_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($credit_record['id'], 'edit', $credit_record, $old_credit_record, 'credit');

			if ($image_id > 0)
            {
                $this->db->where('credit_id', $credit_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('credit_id' => $credit_id));
            }

            $transaction_record = array();

			$transaction_record = array();
			$transaction_record['statement_id'] = $credit_record['statement_id'];

			$transaction_record['date'] = (isset($credit_record['date'])) ? $credit_record['date'] : '';
			$transaction_record['credit'] = $credit_record['amount'];

			$transaction_record['credit_type'] = (isset($credit_record['type'])) ? $credit_record['type'] : '';
			$transaction_record['credit_number'] = (isset($credit_record['number'])) ? $credit_record['number'] : '';
			$transaction_record['credit_name'] = (isset($credit_record['name'])) ? $credit_record['name'] : '';
			$transaction_record['credit_date'] = (isset($credit_record['date'])) ? $credit_record['date'] : '';
			$transaction_record['credit_status'] = (isset($credit_record['type'])) ? $credit_record['type'] : '';

			$transaction_record['statement_type'] = (isset($credit_record['statement_type'])) ? $credit_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($credit_record['statement_number'])) ? $credit_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($credit_record['statement_name'])) ? $credit_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($credit_record['statement_date'])) ? $credit_record['statement_date'] : '';
			$transaction_record['statement_status'] = (isset($credit_record['statement_type'])) ? $credit_record['statement_type'] : '';

            $this->core_model->update('transaction', $old_credit_record['transaction_id'], $transaction_record);

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

	public function ajax_get($credit_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($credit_id <= 0)
			{
				throw new Exception();
			}

			$credit = $this->core_model->get('credit', $credit_id);
			$credit->date_display = date('Y-m-d', $credit->date);
			$credit->amount_display = number_format($credit->amount, 0, '', '');

			$json['credit'] = $credit;
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

	private function _validate_add($credit_record)
	{
		$this->db->where('name', $credit_record['name']);
		$count_user = $this->core_model->count('credit');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($credit_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $credit_id);
		$count_user = $this->core_model->count('credit');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($credit_id, $credit_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $credit_id);
		$count_user = $this->core_model->count('credit');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $credit_id);
		$this->db->where('name', $credit_record['name']);
		$count_user = $this->core_model->count('credit');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}