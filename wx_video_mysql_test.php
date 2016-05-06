<?php


class wxVideo{
	
	public function queryBlogs(){
		$this->connectDB();
		$sql ="select * from blogs "; //SQL语句
		$result = mysql_query($sql); //查询
		while($row = mysql_fetch_array($result))
		{		 
			echo "<div style=\"height:24px; line-height:24px; font-weight:bold;\">"; //排版代码
			echo $row['title'] . "<br/>";
			echo "</div>"; //排版代码
		}
		mysql_close();
	}
	
	public function saveBlog($openid){
		$this->connectDB();
		$sql = 'insert into blogs(title,brief,content,user_id,blog_type_id,created_at,updated_at,note)
			values("'.date("Y-m-d H:i:s").' upload file.","","",1,7,"'.date("Y-m-d H:i:s").'","'.date("Y-m-d H:i:s").'","'.$openid.'")';
		mysql_query($sql);
		$last_id = mysql_insert_id();
		mysql_close();
		return $last_id;
	}
	
	
	public function saveAttachment($blog_id, $openid, $filename, $content_type, $content_size){
		$this->connectDB();
		$sql = 'insert into attachments (attachmentable_id, attachmentable_type, title, note, user_id, created_at, updated_at, attachment_file_name, attachment_content_type, attachment_file_size, attachment_updated_at, is_public) values ('.$blog_id.', "Blog","'.date("Y-m-d H:i:s").' upload file.","'.$openid.'",1,"'.date("Y-m-d H:i:s").'","'.date("Y-m-d H:i:s").'","'.$filename.'","'.$content_type.'",'.$content_size.', "'.date("Y-m-d H:i:s").'", 1)';
		mysql_query($sql);
		$last_id = mysql_insert_id();
		mysql_close();
		return $last_id;
	}
	
	public function updateBlogContent($content, $openid){
		$this->connectDB();
		$sql = 'update blogs set content="'.$content.'" where trim(content)="" and note = "'.$openid.'"';
		mysql_query($sql);
		$last_id = mysql_affected_rows();
		mysql_close();
		return $last_id;
	}
	
	public function selectEmptyBlog($openid){
		$this->connectDB();
		$sql = 'select * from blogs where trim(content)="" and note = "'.$openid.'"';
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);
		if($num == 0){
			return false;
		}else {
			return true;
		}
	}
	
	
	private function connectDB(){		
		$conn=mysql_connect('localhost','account','password') or die("error connecting") ; //连接数据库
		mysql_query("set names 'utf8'"); //数据库输出编码
		mysql_select_db('database'); //打开数据库
	}
}


?>