<?php
namespace RestApi\Models;

use App\Models\Crud_model;

class ProjectCommentsModel extends Crud_model {
	public function get_search_suggestion($search = "", $options = []) {
		$project_comments_table = $this->db->prefixTable('project_comments');
		if ($search) $search = $this->db->escapeLikeString($search);

		$sql = "SELECT * FROM $project_comments_table  
        WHERE $project_comments_table.deleted=0 AND $project_comments_table.description LIKE '%$search%'
        ORDER BY $project_comments_table.created_at DESC LIMIT 0, 10";

		return $this->db->query($sql);
	}
}
