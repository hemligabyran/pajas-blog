<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Admin_Blog extends Admincontroller
{

	public function before()
	{
		parent::before();
		xml::to_XML(array('admin_page' => 'Blog'), $this->xml_meta);
	}

	public function action_index()
	{
		$blogposts = Blogposts::factory()->older_than(NULL)->limit(100)->get();
		$blogposts = $this->format_blogposts($blogposts);

		xml::to_XML($blogposts, array('blogposts' => $this->xml_content), 'blogpost', 'id');
	}

	public function action_blogpost()
	{
		if ( ! empty($_GET['id']))
		{
			$blogpost  = Blogpost::factory($_GET['id']);
			$form_data = $blogpost->get();
		}
		else
		{
			$blogpost  = new Blogpost();
			$form_data = array();
		}

		// Save a Blogpost
		if ( ! empty($_POST))
		{
			$post = new Validation($_POST);
			$form_data = $post->as_array();
			$post->rule('Valid::not_empty', 'title');
			$post->rule('Valid::not_empty', 'content');

			/*
			if (isset($form_data['on_first_page'])) $form_data['on_first_page'] = 1;
			else                                    $form_data['on_first_page'] = 0;
			*/

			if ($post->validate())
			{
				$new_data = $post->as_array();
				if (isset($_GET['id'])) $new_data['id'] = $_GET['id'];
				$blogpost->set($new_data);
				$blogpost->save();

				if ( ! isset($_GET['id']))
				{
					$this->add_message('Blogpost created', FALSE, TRUE);
					$this->redirect('/admin/blog/blogpost?id='.$blogpost->id);
				}
				else
					$this->add_message('The blogpost was updated');
			}
			else
			{
				$errors = $post->errors();

				if (isset($errors['title']))   $errors['title']   = 'Title can\'t be empty.';
				if (isset($errors['content'])) $errors['content'] = 'Content can\'t be empty.';

				foreach ($errors as $error)
					$this->add_error($error);
			}
		}

		xml::to_XML($blogpost->get(), array('blogpost' => $this->xml_content), NULL, 'id');

		$this->set_formdata($form_data);
	}

	public function action_rm()
	{
		if (is_numeric($_GET['id']))
		{
			if (Blogpost::factory($_GET['id'])->rm())
				$this->redirect('/admin/blog');
			else
				$this->add_message('The blogpost couldn\'t be deleted', TRUE);
		}
	}

	protected function format_blogposts($blogposts)
	{
		foreach ($blogposts as $id => $blogpost)
			$blogposts[$id] = $this->format_blogpost($blogpost);

		return $blogposts;
	}

	protected function format_blogpost($blogpost)
	{
		$counter = 1;
		foreach ($blogpost['tags'] as $name => $values)
		{
			foreach ($values as $value)
			{
				$counter++;
				$blogpost['tags'][$counter.'tag'] = array(
					'@name'  => $name,
					'$value' => $value,
				);
			}
			unset($blogpost['tags'][$name]);
		}

		return $blogpost;
	}

}