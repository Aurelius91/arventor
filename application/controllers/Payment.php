<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class payment extends CI_Controller
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

		if (!isset($acl['payment']) || $acl['payment']->add <= 0)
		{
			redirect(base_url());
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Payment';
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('payment_add', $arr_data);
	}

	public function edit($payment_id = 0)
	{
		$acl = $this->_acl;

		if ($payment_id <= 0)
		{
			redirect(base_url());
		}

		if (!isset($acl['payment']) || $acl['payment']->edit <= 0)
		{
			redirect(base_url());
		}

		$payment = $this->core_model->get('payment', $payment_id);

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Payment';
		$arr_data['payment'] = $payment;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('payment_edit', $arr_data);
	}

	public function view($type = 'sale', $id = 0)
	{
		$acl = $this->_acl;

		if (!isset($acl['payment']) || $acl['payment']->list <= 0)
		{
			redirect(base_url());
		}

		if ($type == '' || ($type != 'sale' && $type != 'purchase'))
		{
			redirect(base_url());
		}

		if ($id <= 0)
		{
			redirect(base_url());
		}

		$content = $this->core_model->get($type, $id);
		$remain = $content->total;

		$this->db->where("{$type}_id", $id);
		$this->db->order_by("date DESC");
		$arr_payment = $this->core_model->get('payment');

		foreach ($arr_payment as $payment)
		{
			$payment->date_display = date('d F Y', $payment->date);
			$payment->amount_display = number_format($payment->amount, 0, ',', '.');

			$remain = $remain - $payment->amount;
		}

		$remain_display = number_format($remain, 0, ',', '.');

		$date_display = date('Y-m-d', time());

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = ucfirst($type);
		$arr_data['arr_payment'] = $arr_payment;
		$arr_data['content'] = $content;
		$arr_data['remain'] = $remain;
		$arr_data['remain_display'] = $remain_display;
		$arr_data['date_display'] = $date_display;
		$arr_data['arr_statement'] = $this->_get_statement();
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('payment', $arr_data);
	}




	public function ajax_add($type, $id)
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

			if (!isset($acl['payment']) || $acl['payment']->add <= 0)
			{
				throw new Exception('You have no access to add payment. Please contact your administrator.');
			}

			$payment_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'image_id')
				{
					$image_id = $v;
				}
				else
				{
					$payment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$table = $this->core_model->get($type, $id);

			$payment_record["{$type}_id"] = $table->id;

			$payment_record["{$type}_type"] = $table->type;
			$payment_record["{$type}_number"] = $table->number;
			$payment_record["{$type}_name"] = $table->name;
			$payment_record["{$type}_date"] = $table->date;
			$payment_record["{$type}_status"] = $table->status;

			if ($type == 'sale')
			{
				$payment_record['customer_id'] = $table->customer_id;
				$payment_record['customer_type'] = $table->customer_type;
				$payment_record['customer_number'] = $table->customer_number;
				$payment_record['customer_name'] = $table->customer_name;
				$payment_record['customer_date'] = $table->customer_date;
				$payment_record['customer_status'] = $table->customer_status;
			}
			else
			{
				$payment_record['vendor_id'] = $table->vendor_id;
				$payment_record['vendor_type'] = $table->vendor_type;
				$payment_record['vendor_number'] = $table->vendor_number;
				$payment_record['vendor_name'] = $table->vendor_name;
				$payment_record['vendor_date'] = $table->vendor_date;
				$payment_record['vendor_status'] = $table->vendor_status;
			}

			$payment_record = $this->cms_function->populate_foreign_field($payment_record['statement_id'], $payment_record, 'statement');

			$payment_id = $this->core_model->insert('payment', $payment_record);
			$payment_record['id'] = $payment_id;
			$payment_record['name'] = $payment_record["{$type}_number"];
			$payment_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($payment_record['id'], 'add', $payment_record, array(), 'payment');

			if ($image_id > 0)
			{
				$this->core_model->update('image', $image_id, array('payment_id' => $payment_id));
			}

			// insert transaction
			$transaction_record = array();
			$transaction_record["{$type}_id"] = $table->id;
			$transaction_record['statement_id'] = (isset($payment_record['statement_id'])) ? $payment_record['statement_id'] : 0;
			$transaction_record['payment_id'] = $payment_id;

			$transaction_record['date'] = $payment_record['date'];

			$transaction_record["{$type}_type"] = (isset($payment_record["{$type}_type"])) ? $payment_record["{$type}_type"] : '';
			$transaction_record["{$type}_number"] = (isset($payment_record["{$type}_number"])) ? $payment_record["{$type}_number"] : '';
			$transaction_record["{$type}_name"] = (isset($payment_record["{$type}_name"])) ? $payment_record["{$type}_name"] : '';
			$transaction_record["{$type}_date"] = (isset($payment_record["{$type}_date"])) ? $payment_record["{$type}_date"] : 0;
			$transaction_record["{$type}_status"] = (isset($payment_record["{$type}_status"])) ? $payment_record["{$type}_status"] : '';

			$transaction_record['statement_type'] = (isset($payment_record['statement_type'])) ? $payment_record['statement_type'] : '';
			$transaction_record['statement_number'] = (isset($payment_record['statement_number'])) ? $payment_record['statement_number'] : '';
			$transaction_record['statement_name'] = (isset($payment_record['statement_name'])) ? $payment_record['statement_name'] : '';
			$transaction_record['statement_date'] = (isset($payment_record['statement_date'])) ? $payment_record['statement_date'] : 0;
			$transaction_record['statement_status'] = (isset($payment_record['statement_status'])) ? $payment_record['statement_status'] : '';

			if ($type == 'sale')
			{
				$transaction_record['customer_id'] = (isset($payment_record['customer_id'])) ? $payment_record['customer_id'] : 0;
				$transaction_record['customer_type'] = (isset($payment_record['customer_type'])) ? $payment_record['customer_type'] : '';
				$transaction_record['customer_number'] = (isset($payment_record['customer_number'])) ? $payment_record['customer_number'] : '';
				$transaction_record['customer_name'] = (isset($payment_record['customer_name'])) ? $payment_record['customer_name'] : '';
				$transaction_record['customer_date'] = (isset($payment_record['customer_date'])) ? $payment_record['customer_date'] : 0;
				$transaction_record['customer_status'] = (isset($payment_record['customer_status'])) ? $payment_record['customer_status'] : '';

				$transaction_record['debit'] = $payment_record['amount'];
			}
			else
			{
				$transaction_record['vendor_id'] = (isset($payment_record['vendor_id'])) ? $payment_record['vendor_id'] : 0;
				$transaction_record['vendor_type'] = (isset($payment_record['vendor_type'])) ? $payment_record['vendor_type'] : '';
				$transaction_record['vendor_number'] = (isset($payment_record['vendor_number'])) ? $payment_record['vendor_number'] : '';
				$transaction_record['vendor_name'] = (isset($payment_record['vendor_name'])) ? $payment_record['vendor_name'] : '';
				$transaction_record['vendor_date'] = (isset($payment_record['vendor_date'])) ? $payment_record['vendor_date'] : 0;
				$transaction_record['vendor_status'] = (isset($payment_record['vendor_status'])) ? $payment_record['vendor_status'] : '';

				$transaction_record['credit'] = $payment_record['amount'];
			}

			$transaction_record['payment_type'] = (isset($payment_record['type'])) ? $payment_record['type'] : '';
			$transaction_record['payment_number'] = (isset($payment_record['number'])) ? $payment_record['number'] : '';
			$transaction_record['payment_name'] = (isset($payment_record['name'])) ? $payment_record['name'] : '';
			$transaction_record['payment_date'] = (isset($payment_record['date'])) ? $payment_record['date'] : 0;
			$transaction_record['payment_status'] = (isset($payment_record['status'])) ? $payment_record['status'] : '';
			$this->core_model->insert('transaction', $transaction_record);

			// update table status
			$this->db->where("{$type}_id", $table->id);
			$arr_all_payment = $this->core_model->get('payment');

			$total = 0;

			foreach ($arr_all_payment as $all_payment)
			{
				$total += $all_payment->amount;
			}

			if ($total >= $table->total)
			{
				$this->core_model->update($type, $table->id, array(
					'status' => 'Paid'
				));

				// update payment
				$this->core_model->update('payment', $payment_id, array(
					"{$type}_status" => 'Paid'
				));
			}
			else
			{
				$this->core_model->update($type, $table->id, array(
					'status' => 'Pending'
				));

				// update payment
				$this->core_model->update('payment', $payment_id, array(
					"{$type}_status" => 'Paid'
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

	public function ajax_change_status($payment_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($payment_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['payment']) || $acl['payment']->edit <= 0)
			{
				throw new Exception('You have no access to edit payment. Please contact your administrator.');
			}

			$old_payment = $this->core_model->get('payment', $payment_id);

			$old_payment_record = array();

			foreach ($old_payment as $key => $value)
			{
				$old_payment_record[$key] = $value;
			}

			$payment_record = array();

			foreach ($_POST as $k => $v)
			{
				$payment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
			}

			$this->core_model->update('payment', $payment_id, $payment_record);
			$payment_record['id'] = $payment_id;
			$payment_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log('status', $payment_record, $old_payment_record, 'payment');

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

	public function ajax_delete($payment_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			if ($payment_id <= 0)
			{
				throw new Exception();
			}

			if ($this->session->userdata('user_id') != $this->_user->id)
			{
				throw new Exception('Server Error. Please log out first.');
			}

			$acl = $this->_acl;

			if (!isset($acl['payment']) || $acl['payment']->delete <= 0)
			{
				throw new Exception('You have no access to delete payment. Please contact your administrator.');
			}

			$payment = $this->core_model->get('payment', $payment_id);
			$updated = $_POST['updated'];
			$payment_record = array();

			foreach ($payment as $k => $v)
			{
				if ($k == 'updated' && $v != $updated)
				{
					throw new Exception('This data has been updated by another payment. Please refresh the page.');
				}
				else
				{
					$payment_record[$k] = $v;
				}
			}

			$this->_validate_delete($payment_id);

			$this->core_model->delete('payment', $payment_id);
			$payment_record['id'] = $payment->id;
			$payment_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($payment_record['id'], 'delete', $payment_record, array(), 'payment');

			if ($this->_has_image > 0)
			{
				$this->db->where('payment_id', $payment_id);
	            $arr_image = $this->core_model->get('image');

	            foreach ($arr_image as $image)
	            {
	                unlink("images/website/{$image->id}.{$image->ext}");

	                $this->core_model->delete('image', $image->id);
	            }
			}

			// delete transaction
			$this->db->where('payment_id', $payment_id);
			$this->core_model->delete('transaction');

			// update
			if ($payment_record['sale_id'] > 0)
			{
				$this->core_model->update('sale', $payment_record['sale_id'], array(
					'status' => 'Pending'
				));
			}
			else
			{
				$this->core_model->update('purchase', $payment_record['purchase_id'], array(
					'status' => 'Pending'
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

	public function ajax_edit($payment_id)
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

			if (!isset($acl['payment']) || $acl['payment']->edit <= 0)
			{
				throw new Exception('You have no access to edit payment. Please contact your administrator.');
			}

			$old_payment = $this->core_model->get('payment', $payment_id);

			$old_payment_record = array();

			foreach ($old_payment as $key => $value)
			{
				$old_payment_record[$key] = $value;
			}

			$payment_record = array();
			$image_id = 0;

			foreach ($_POST as $k => $v)
			{
				if ($k == 'updated')
				{
					if ($v != $old_payment_record[$k])
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
					$payment_record[$k] = ($k == 'date') ? strtotime($v) : $v;
				}
			}

			$this->_validate_edit($payment_id, $payment_record);

			$this->core_model->update('payment', $payment_id, $payment_record);
			$payment_record['id'] = $payment_id;
			$payment_record['last_query'] = $this->db->last_query();

			$this->cms_function->system_log($payment_record['id'], 'edit', $payment_record, $old_payment_record, 'payment');

			$this->cms_function->update_foreign_field(array('product'), $payment_record, 'payment');

			if ($image_id > 0)
            {
                $this->db->where('payment_id', $payment_id);
                $arr_image = $this->core_model->get('image');

                foreach ($arr_image as $image)
                {
                    unlink("images/website/{$image->id}.{$image->ext}");

                    $this->core_model->delete('image', $image->id);
                }

                $this->core_model->update('image', $image_id, array('payment_id' => $payment_id));
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

	public function ajax_get($payment_id = 0)
	{
		$json['status'] = 'success';

		try
		{
			if ($payment_id <= 0)
			{
				throw new Exception();
			}

			$payment = $this->core_model->get('payment', $payment_id);

			$json['payment'] = $payment;
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

	private function _validate_add($payment_record)
	{
		$this->db->where('name', $payment_record['name']);
		$count_payment = $this->core_model->count('payment');

		if ($count_payment > 0)
		{
			throw new Exception('Name already exist.');
		}
	}

	private function _validate_delete($payment_id)
	{
		$this->db->where('deletable <=', 0);
		$this->db->where('id', $payment_id);
		$count_payment = $this->core_model->count('payment');

		if ($count_payment > 0)
		{
			throw new Exception('Data cannot be deleted.');
		}
	}

	private function _validate_edit($payment_id, $payment_record)
	{
		$this->db->where('editable <=', 0);
		$this->db->where('id', $payment_id);
		$count_payment = $this->core_model->count('payment');

		if ($count_payment > 0)
		{
			throw new Exception('Data cannot be updated.');
		}

		$this->db->where('id !=', $payment_id);
		$this->db->where('name', $payment_record['name']);
		$count_payment = $this->core_model->count('payment');

		if ($count_payment > 0)
		{
			throw new Exception('Name is already exist.');
		}
	}
}