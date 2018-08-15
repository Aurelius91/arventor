<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Image extends CI_Controller
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




	public function product_image($product_color_id)
	{
		$acl = $this->_acl;

		$this->db->where('product_color_id', $product_color_id);
		$arr_image = $this->core_model->get('image');

		foreach ($arr_image as $image)
		{
			$image->image_name = ($image->name != '') ? $image->name : $image->id . '.' . $image->ext;
		}

		$arr_data['setting'] = $this->_setting;
		$arr_data['account'] = $this->_user;
		$arr_data['acl'] = $acl;
		$arr_data['type'] = 'Product';
		$arr_data['arr_image'] = $arr_image;
		$arr_data['product_color_id'] = $product_color_id;
		$arr_data['csrf'] = $this->cms_function->generate_csrf();
		$arr_data['total_size'] = $this->cms_function->check_memory();

		$this->load->view('html', $arr_data);
		$this->load->view('product_image', $arr_data);
	}




	public function ajax_delete($image_id)
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

			if (!isset($acl['product']) || $acl['product']->delete <= 0)
			{
				throw new Exception('You have no access to delete image. Please contact your administrator.');
			}

			$image = $this->core_model->get('image', $image_id);

			unlink("images/website/{$image->id}.{$image->ext}");
			$this->core_model->delete('image', $image->id);

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

	public function ajax_upload($product_color_id)
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			$source_path = $_FILES['file']['tmp_name'];
			$ext = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);

			list($width, $height) = getimagesize($source_path);
			$filesize = ((filesize($source_path) / 1024) / 1024);

			if ($filesize > 0.5)
			{
				throw new Exception('Recommended filesize for image is 500kb.');
			}

			if ($width <= 0 || $height <= 0)
			{
				throw new Exception('The file your uploading is not image. Please upload image type file.');
			}

			$image_record['product_color_id'] = $product_color_id;
			$image_record['ext'] = $ext;

			$image_record = $this->cms_function->populate_foreign_field($image_record['product_color_id'], $image_record, 'product_color');

			$image_id = $this->core_model->insert('image', $image_record);

			$target_path = 'images/website/' . $image_id . '.' . $ext;
			move_uploaded_file($source_path, $target_path);
			chmod($target_path, 0777);

			$json['ext'] = $ext;
			$json['image_id'] = $image_id;

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

	public function ajax_upload_all($type = '', $page = '')
	{
		$json['status'] = 'success';

		try
		{
			$this->db->trans_start();

			$source_path = $_FILES['file']['tmp_name'];
			$ext = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);

			list($width, $height) = getimagesize($source_path);
			$filesize = ((filesize($source_path) / 1024) / 1024);
			$type = urldecode($type);
			$page = urldecode($page);

			if ($filesize > 0.5)
			{
				throw new Exception('Recommended filesize for image is 500kb.');
			}

			if ($width <= 0 || $height <= 0)
			{
				throw new Exception('The file your uploading is not image. Please upload image type file.');
			}

			$image_record['ext'] = $ext;
			$image_record['type'] = $type;
			$image_record['page'] = $page;
			$image_id = $this->core_model->insert('image', $image_record);

			$target_path = 'images/website/' . $image_id . '.' . $ext;
			move_uploaded_file($source_path, $target_path);
			chmod($target_path, 0777);

			$json['ext'] = $ext;
			$json['image_id'] = $image_id;

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
}