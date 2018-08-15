<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class statement extends CI_Controller
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

		if (!isset($acl['statement']) || $acl['statement']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'statement';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('statement_add', $arr_data);
	}

	public function edit($statement_id = 0)
	{
		$acl = $this->_acl;

		if ($statement_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['statement']) || $acl['statement']->edit <= 0)
		{
			redirect(base_url());
		}

		$statement = $this->core_model->get('statement', $statement_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'statement';
		$arr_data['statement'] = $statement;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('statement_edit', $arr_data);
	}

	public function view($page = 1, $filter = 'all', $query = '')
	{
		$acl = $this->_acl;

		if (!isset($acl['statement']) || $acl['statement']->list <= 0)
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
		$arr_statement = $this->core_model->get('statement');
		$arr_statement_id = $this->cms_function->extract_records($arr_statement, 'id');

		$arr_tranasction_lookup = array();

		if (count($arr_statement_id) > 0)
		{
			$this->db->order_by('date DESC');
			$this->db->where_in('statement_id', $arr_statement_id);
			$arr_transaction  = $this->core_model->get('transaction');

			foreach ($arr_transaction as $transaction)
			{
				if (!isset($arr_tranasction_lookup[$transaction->statement_id]))
				{
					$arr_tranasction_lookup[$transaction->statement_id] = 0;
				}

				$arr_tranasction_lookup[$transaction->statement_id] += $transaction->debit;
				$arr_tranasction_lookup[$transaction->statement_id] -= $transaction->credit;
			}
		}

		foreach ($arr_statement as $statement)
		{
			$statement->final_amount = (isset($arr_tranasction_lookup[$statement->id])) ? $arr_tranasction_lookup[$statement->id] : 0;
			$statement->final_amount_display = number_format($statement->final_amount, 0, ',', '.');
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

		$count_statement = $this->core_model->count('statement');
		$count_page = ceil($count_statement / $this->_setting->setting__limit_page);

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'statement';
		$arr_data['page'] = $page;
		$arr_data['count_page'] = $count_page;
		$arr_data['query'] = $query;
		$arr_data['filter'] = $filter;
		$arr_data['arr_statement'] = $arr_statement;
		$arr_data['date_display'] = $date_display;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('statement', $arr_data);
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

			if (!isset($acl['statement']) || $acl['statement']->add <= 0)
			{
				throw new Exception('You have no access to add statement. Please contact your administrator.');
			}

			$statement_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$statement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_add($statement_record);

			$statement_id = $this->core_model->insert('statement', $statement_record);
			$statement_record['id'] = $statement_id;
			$statement_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($statement_record['id'], 'add', $statement_record, array(), 'statement');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('statement_id' => $statement_id));
			}

			// add transation
			if ($statement_record['amount'] != 0)
			{
				$transaction_record = array();
				$transaction_record['statement_id'] = $statement_id;

				$transaction_record['date'] = (isset($statement_record['date'])) ? $statement_record['date'] : '';

				if ($statement_record['amount'] > 0)
				{
					$transaction_record['debit'] = $statement_record['amount'];
				}
				else
				{
					$transaction_record['credit'] = $statement_record['amount'];
				}

				$transaction_record['statement_type'] = (isset($statement_record['type'])) ? $statement_record['type'] : '';
				$transaction_record['statement_number'] = (isset($statement_record['number'])) ? $statement_record['number'] : '';
				$transaction_record['statement_name'] = (isset($statement_record['name'])) ? $statement_record['name'] : '';
				$transaction_record['statement_date'] = (isset($statement_record['date'])) ? $statement_record['date'] : '';
				$transaction_record['statement_status'] = (isset($statement_record['type'])) ? $statement_record['type'] : '';
				$transaction_id = $this->core_model->insert('transaction', $transaction_record);

				// update transaction_id in statement
				$this->core_model->update('statement', $statement_id, array(
					'transaction_id' => $transaction_id,
					'transaction_date' => $transaction_record['statement_date']
				));
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

	public function ajax_change_status($statement_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($statement_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['statement']) || $acl['statement']->edit <= 0)
			{
				throw new Exception('You have no access to edit statement. Please contact your administrator.');
			}

			$old_statement = $this->core_model->get('statement', $statement_id);

			$old_statement_record = array();

			foreach ($old_statement as $key => $value)
			{
				$old_statement_record[$key] = $value;
			}

			$statement_record = array();

			foreach ($_POST as $k => $v)
			{
				$statement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('statement', $statement_id, $statement_record);
			$statement_record['id'] = $statement_id;
			$statement_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $statement_record, $old_statement_record, 'statement');

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

	public function ajax_delete($statement_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($statement_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['statement']) || $acl['statement']->delete <= 0)
			{
				throw new Exception('You have no access to delete statement. Please contact your administrator.');
			}

			$statement = $this->core_model->get('statement', $statement_id);
			$updated = $_POST['updated'];
			$statement_record = array();

			foreach ($statement as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another statement. Please refresh the page.');
				}
				else
				{
					$statement_record[$k] = $v;
				}
			}

			$this->_validate_delete($statement_id);

			$this->core_model->delete('statement', $statement_id);
			$statement_record['id'] = $statement->id;
			$statement_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($statement_record['id'], 'delete', $statement_record, array(), 'statement');

			if ($this->_has_image > 0)
			{
				$this->db->where('statement_id', $statement_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			$this->core_model->delete('transaction', $statement_record['transaction_id']);

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

	public function ajax_edit($statement_id)
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

			if (!isset($acl['statement']) || $acl['statement']->edit <= 0)
			{
				throw new Exception('You have no access to edit statement. Please contact your administrator.');
			}

			$old_statement = $this->core_model->get('statement', $statement_id);

			$old_statement_record = array();

			foreach ($old_statement as $key => $value)
			{
				$old_statement_record[$key] = $value;
			}

			$statement_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_statement_record[$k])
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
					$statement_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($statement_id, $statement_record);

			$this->core_model->update('statement', $statement_id, $statement_record);
			$statement_record['id'] = $statement_id;
			$statement_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($statement_record['id'], 'edit', $statement_record, $old_statement_record, 'statement');

			$this->cms_function->update_foreign_field(array('credit', 'debit', 'payment', 'purchase', 'sale', 'transaction', 'transfer'), $statement_record, 'statement');

			if ($image_id > 0)
            {
                $this->db->where('statement_id', $statement_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('statement_id' => $statement_id));
            }

            // edit transaction
            if ($statement_record['amount'] != 0)
            {
            	if ($old_statement_record['transaction_id'] > 0)
            	{
            		$transaction_record = array();
					$transaction_record['date'] = (isset($statement_record['date'])) ? $statement_record['date'] : '';

					if ($statement_record['amount'] > 0)
					{
						$transaction_record['debit'] = $statement_record['amount'];
					}
					else
					{
						$transaction_record['credit'] = $statement_record['amount'];
					}

		            $this->core_model->update('transaction', $old_statement_record['transaction_id'], $transaction_record);
            	}
            	else
            	{
            		$transaction_record = array();
					$transaction_record['statement_id'] = $statement_id;

					$transaction_record['date'] = (isset($statement_record['date'])) ? $statement_record['date'] : '';

					if ($statement_record['amount'] > 0)
					{
						$transaction_record['debit'] = $statement_record['amount'];
					}
					else
					{
						$transaction_record['credit'] = $statement_record['amount'];
					}

					$transaction_record['statement_type'] = (isset($statement_record['type'])) ? $statement_record['type'] : '';
					$transaction_record['statement_number'] = (isset($statement_record['number'])) ? $statement_record['number'] : '';
					$transaction_record['statement_name'] = (isset($statement_record['name'])) ? $statement_record['name'] : '';
					$transaction_record['statement_date'] = (isset($statement_record['date'])) ? $statement_record['date'] : '';
					$transaction_record['statement_status'] = (isset($statement_record['type'])) ? $statement_record['type'] : '';
					$transaction_id = $this->core_model->insert('transaction', $transaction_record);

					// update transaction_id in statement
					$this->core_model->update('statement', $statement_id, array(
						'transaction_id' => $transaction_id,
						'transaction_date' => $transaction_record['statement_date']
					));
            	}
            }
            else
            {
            	$this->core_model->delete('transaction', $old_statement_record['transaction_id']);
            	$this->core_model->update('statement', $statement_id, array('transaction_id' => 0, 'transaction_date' => 0));
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

	public function ajax_get($statement_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($statement_id <= 0)
			{
				throw new Exception();
			}

			$statement = $this->core_model->get('statement', $statement_id);
			$statement->date_display = ($statement->date <= 0) ? date('Y-m-d', time()) : date('Y-m-d', $statement->date);
			$statement->amount_display = number_format($statement->amount, 0, '', '');

			$json['statement'] = $statement;
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




	private function _validate_add($statement_record)
	{
		$this->db->where('name', $statement_record['name']);
		$count_user = $this->core_model->count('statement');

		if ($count_user > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($statement_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $statement_id);
		$count_user = $this->core_model->count('statement');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($statement_id, $statement_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $statement_id);
		$count_user = $this->core_model->count('statement');

		if ($count_user > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $statement_id);
		$this->db->where('name', $statement_record['name']);
		$count_user = $this->core_model->count('statement');

		if ($count_user > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}