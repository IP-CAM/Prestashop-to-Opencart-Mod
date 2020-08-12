<?php
	class banco
	{	
		public function __construct($host, $user, $pass, $base) {
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;
			$this->base = $base;
		}

		public function connect ()
		{
			$connect = mysqli_connect(
				$this->host,
				$this->user,
				$this->pass,
				$this->base
			);
			mysqli_query($connect,"SET NAMES 'utf8'");
			mysqli_query($connect,"SET character_set_connection=utf8");
			mysqli_query($connect,"SET character_set_client=utf8");
			mysqli_query($connect,"SET character_set_results=utf8");
			return $connect;
		}

		public function query ($sql)
		{
			$query = mysqli_query($this->connect(), $sql);
			return $query;
		}

		public function assoc ($sql)
		{
			$query = $this->query($sql);
			$i = 0;
			while($assoc = mysqli_fetch_assoc($query)){
				$return[$i] = $assoc;
				$i++;
			}
			return @$return;
		}

		public function numRows ($sql)
		{
			$query = $this->query($sql);
			return mysqli_num_rows($query);
		}

		public function lastId($id, $table)
		{
			$sql = sprintf("SELECT `%s` FROM `%s` ORDER BY `%s` DESC LIMIT 0, 1", $id, $table, $id);
			$return = $this->assoc($sql);
			return $return[0][$id];
		}
    }

?>
