<?php defined('SYSPATH') or die('No direct script access.');

class Model_Blogpost extends Model
{

	protected $data;

	public function __construct($id = FALSE)
	{
		parent::__construct();

		if (Kohana::$environment == Kohana::DEVELOPMENT)
			Blogposts::factory(); // Runs create db structure

		$this->load_from_db($id);
	}

	public static function factory_by_path($path)
	{
		foreach (Blogposts::factory()->paths($path)->get() as $blogpost)
			return new self($blogpost['id']);

		return FALSE;
	}

	public function load_from_db($id)
	{
		foreach (Blogposts::factory()->older_than(NULL)->ids($id)->get() as $blogpost)
			$this->data = $blogpost;

		return $this;
	}

	public function save()
	{
		if ( ! is_array($this->data)) return FALSE;

		if ( ! isset($this->data['id']))
		{
			$this->pdo->exec('INSERT INTO blog_posts (title) VALUES(\'\')');
			$this->data['id'] = $this->pdo->lastInsertId();
		}

		$this->data['id'] = intval($this->data['id']);

		foreach ($this->data as $key => $value)
		{
			$basic_sql = 'UPDATE blog_posts SET ';
			if (in_array($key, array('title', 'path', 'content', 'published')))
				$basic_sql .= Mysql::quote_identifier($key).' = '.$this->pdo->quote(Encoding::fixUTF8($value));
			elseif ($key == 'tags')
			{
				$this->pdo->exec('DELETE FROM blog_posts_tags WHERE post_id = '.$this->data['id']);
				$tags_sql = 'INSERT INTO blog_posts_tags (post_id, name, value) VALUES';
				foreach ($value as $tag_name => $tag_values)
				{
					if ( ! is_array($tag_values)) $tag_values = array($tag_values);

					foreach ($tag_values as $tag_value)
						$tags_sql .= '('.$this->data['id'].','.$this->pdo->quote($tag_name).','.$this->pdo->quote($tag_value).'),';
				}

				$tags_sql = rtrim($tags_sql, ',');
				$this->pdo->exec($tags_sql);
			}

			$basic_sql .= ' WHERE id = '.$this->data['id'];
			$this->pdo->exec($basic_sql);
		}

		// Reload to make sure we got the right shit
		$this->load_from_db($this->data['id']);

		return $this;
	}

	public function rm()
	{
		if (isset($this->data['id']))
		{
			$this->pdo->exec('DELETE FROM blog_posts_tags WHERE post_id = '.intval($this->data['id']));
			$this->pdo->exec('DELETE FROM blog_posts      WHERE id      = '.intval($this->data['id']));
		}

		$this->data = NULL;

		return TRUE;
	}

}