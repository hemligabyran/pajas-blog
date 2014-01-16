<?php defined('SYSPATH') or die('No direct script access.');

class Model_Blogposts extends Model
{

	protected $ids;
	protected $limit       = 10;
	protected $offset;
	protected $older_than;
	protected $order_by    = array('published' => 'DESC');
	protected $paths;
	protected $tags;

	public function __construct()
	{
		parent::__construct();

		if (Kohana::$environment == Kohana::DEVELOPMENT)
			$this->create_db_structure();

		$this->older_than = time();
	}

	protected function create_db_structure()
	{
		// Make PDO silent during these checks
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

		$db_check_pass = TRUE;

		$result = $this->pdo->query('DESCRIBE blog_posts;');
		if ($result) $result = $result->fetchAll(PDO::FETCH_ASSOC);
		if ( ! $result) $db_check_pass = FALSE;

		$result = $this->pdo->query('DESCRIBE blog_posts_tags;');
		if ($result) $result = $result->fetchAll(PDO::FETCH_ASSOC);
		if ( ! $result) $db_check_pass = FALSE;

		if ( ! $db_check_pass)
		{
			$this->pdo->exec('CREATE TABLE blog_posts (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					uri varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					content text COLLATE utf8_unicode_ci NOT NULL,
					published timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

			$this->pdo->exec('CREATE TABLE blog_posts_tags (
					post_id bigint(20) unsigned NOT NULL,
					name varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					value varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (post_id,name,value),
					KEY post_id_idx (post_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

			$this->pdo->exec('ALTER TABLE `blog_posts_tags`
					ADD CONSTRAINT blog_posts_tags_ibfk_1 FOREIGN KEY (post_id) REFERENCES blog_posts (id) ON DELETE NO ACTION ON UPDATE NO ACTION;');
		}
	}

	public function get()
	{
		$sql = 'SELECT p.* FROM blog_posts p WHERE 1';

		if ($this->paths !== NULL)
		{
			if ($this->paths === FALSE) $sql .= ' AND 1 = 2';
			else
			{
				$sql .= ' AND p.path IN (';
				foreach ($this->paths as $path)
					$sql .= $this->pdo->quote($path).',';
				$sql = rtrim($sql, ',').')';
			}
		}

		if ($this->ids !== NULL)
			$sql .= ' AND p.id IN ('.implode(',', $this->ids).')';

		if ($this->tags !== NULL)
		{
			foreach ($this->tags as $tag_name => $tag_values)
			{
				if ($tag_values === TRUE)
					$sql .= ' AND p.id IN (SELECT post_id FROM blog_posts_tags WHERE name = '.$this->pdo->quote($tag_name).')';
				elseif (is_array($tag_values))
				{
					foreach ($tag_values as $tag_value)
						$sql .= ' AND p.id IN (SELECT post_id FROM blog_posts_tags WHERE name = '.$this->pdo->quote($tag_name).' AND value = '.$this->pdo->quote($tag_value).')';
				}
				else
					$sql .= ' AND p.id IN (SELECT post_id FROM blog_posts_tags WHERE name = '.$this->pdo->quote($tag_name).' AND value = '.$this->pdo->quote($tag_values).')';
			}
		}

		if ($this->order_by)
		{
			$sql .= "\nORDER BY";
			foreach ($this->order_by as $key => $value)
			{
				if ( ! is_array($value)) $value = array($key => $value);

				foreach ($value as $field => $order)
				{
					if (strtoupper($order) == 'ASC') $order = 'ASC';
					else                             $order = 'DESC';

					$sql .= ' '.Mysql::quote_identifier($field).' '.$order;
				}
			}
		}

		if (isset($this->limit))
		{
			$sql .= ' LIMIT '.$this->limit;

			if (isset($this->offset))
				$sql .= ' OFFSET '.$this->offset;
		}

		$post_ids  = array();
		$blogposts = array();
		foreach ($this->pdo->query($sql) as $row)
		{
			foreach ($row as $key => $value)
				if (is_numeric($key))
					unset($row[$key]);

			$row['tags']           = array();
			$blogposts[$row['id']] = $row;
			$post_ids[]            = $row['id'];
		}

		if ( ! empty($post_ids))
		{
			$sql = 'SELECT * FROM blog_posts_tags WHERE post_id IN ('.implode(',', $post_ids).')';
			foreach ($this->pdo->query($sql) as $row)
			{
				if ( ! isset($blogposts[$row['post_id']]['tags'][$row['name']]))
					$blogposts[$row['post_id']]['tags'][$row['name']] = array();

				$blogposts[$row['post_id']]['tags'][$row['name']][] = $row['value'];
			}
		}

		return $blogposts;
	}

	public function ids($array)
	{
		if ($array === NULL)   $this->ids = NULL;
		elseif (empty($array)) $this->ids = array(-1);
		else
		{
			if ( ! is_array($array)) $array = array($array);

			array_map('intval', $array);

			$this->ids = $array;
		}

		return $this;
	}

	public function limit($int)
	{
		if ($int === NULL) $this->limit = NULL;
		else               $this->limit = (int) $int;

		return $this;
	}

	public function offset($int)
	{
		if ($int === NULL) $this->offset = NULL;
		else               $this->offset = (int) $int;

		return $this;
	}

	public function older_than($numval)
	{
		if ($numval === NULL) $this->older_than = NULL;
		else
		{
			$numval = preg_replace('/[^0-9]+/', '', $numval);
			$this->older_than = floatval($numval); // int can be capped, use float instead!
		}

		return $this;
	}

	public function order_by($array)
	{
		if (is_array($array)) $this->order_by = $array;
		else                  $this->order_by = NULL;

		return $this;
	}

	public function paths($array)
	{
		if ($array === NULL) $this->paths = NULL;
		elseif ( ! is_array($array)) $array = array($array);
		elseif (empty($array))       $array = FALSE;

		$this->paths = $array;

		return $this;
	}

	public function tags($array)
	{
		if (is_array($array)) $this->tags = $array;
		else                  $this->tags = NULL;

		return $this;
	}

}