<?php
class Service_model extends Model {

    function Service_model()
    {
        parent::Model();
				$this->load->database();
		}
	
		function add_job($filename)
		{
			$datestring = date("Y-m-d");
			$data = array(
				'title' => "I will ".$this->input->post('quicktitle'),
				'category' => $this->input->post('category'),
				'description' => $this->input->post('jobdescription'),
				'information_required' => $this->input->post('informationrequired'),
				'tags' => $this->input->post('keywords'),
				'maxnumofdays' => $this->input->post('maxdays'),
				'image' => $filename,
				'date_added' => $datestring,
				'member_posted_by' => $this->session->userdata('id'),
				'approved' => 'y'
				);
				
			$this->db->insert('gigs', $data);
			
			$id = mysql_insert_id();
			
			//Check if the user is in level 2, otherwise upgrade the user level to 2 making him a seller
			$this->db->where('id',$this->session->userdata('id'));
			$query = $this->db->get('members');
			$row = $query->row();
			
			if($row->level != '2' and $row->level != '-1')
			{
				//Upgrade the level
				$data = array('level' => '2');
				$this->db->where('id',$this->session->userdata('id'));
				$query = $this->db->update('members',$data);
				
				// Check the current level and upgrade it
				if($this->session->userdata('level') == '1')
				{
					$data = array('level' => '2');

		      $this->session->set_userdata($data);
					
				}
			}
			
			return $id;
		}
		function get_job_listing($id)
		{
			//Get the job title
			$data['tags'] = "";
			$this->db->where('approved','y');
			//$this->db->where('member_disabled','n');
			$this->db->where('id',$id);
				$query = $this->db->get('gigs');
			$row = $query->row();
			
			$data['title'] = $row->title;
			$data['category'] = $row->category;
			$data['description'] = $row->description;
			$data['maxnum'] = $row->maxnumofdays;
			//$data['tags'] = $row->tags;
			$tags = explode(' ',$row->tags);
			foreach($tags as $tag)
			{
				if($tag != "")
				{
					$data['tags'] .= "<span>$tag</span>";
				}
			}
			if($row->image != "")
			{
			$data['image'] = $row->image;
			}
			else {
				$data['image'] = "blank_image.png";
			}
			$data['date_added'] = $row->date_added;
			$data['posted_by'] = $row->member_posted_by;
			$data['information_required'] = $row->information_required;
			
			//Increment the counter of number of time the listing is viewed
					$this->db->where('id',$id);
					$query = $this->db->get('gigs');
					$row = $query->row();
					$current = $row->views;

					$new = $current + 1;
					$data1 = array('views' => $new);

					$this->db->where('id',$id);
					$this->db->update('gigs',$data1);
			
			return $data;
		}
			function get_filtered_listing($filter,$num,$offset)
			{
					$list = "";
					if(!is_numeric($filter))
					{
						if($filter == "latest")
						{
							$this->db->orderby('id',"DESC");
						
						}
						if($filter == "mostviewed")
						{
							$this->db->orderby('views',"DESC");
							
						}
					}
					else {
						$this->db->where('category',$filter);						
					}
					
					$this->db->where('approved','y');
					$this->db->where('member_disabled','n');
					$query = $this->db->get('gigs',$num,$offset);
					$filter_title = $this->get_filtered_job_title($filter);
					$list .= "<div id='filter_title'><b>Filtered by:</b> <span>".$filter_title."</span></div>";
						foreach($query->result() as $row)
						{
								$list .= '<div id=single_listing>
									<div id=single_listing_image><img src=\''.base_url().'/assets/timthumb.php?src='.base_url().'/images/gigs/'.$row->image.'&h=70&w=100&zc=1\' alt=""><br/>
											</div>
									<div id=single_listing_content><a href='.site_url('service/single/'.$row->id).' class=listing-title>'.$row->title.' </a>'.
									$row->description.'</div>

										<div id=order><a href='.site_url('service/single/'.$row->id).' class="small yellow awesome">Read more</a></div>
										<div id=meta>Posted by: <a href="'.site_url('user/view/'.$this->Common_model->getusername($row->member_posted_by)).'">'.$this->Common_model->convertname($row->member_posted_by).'</a> on '.$this->Common_model->convert_date($row->date_added).'</div>
											<div id=share>Comment | Favorite | Save</div>
										</div>';
						}
					return $list;
			}
		function get_job_title($id)
		{
			//Get the job title
			$this->db->where('approved','y');
			$this->db->where('id',$id);
			$query = $this->db->get('gigs');
			$row = $query->row();
			
			$title = $row->title;
		
			return $title;
		}
		function get_filtered_job_title($filter)
		{
			//Get the job title
			if(!is_numeric($filter))
			{
				if($filter == "latest")
				{
					$title = "Latest Jobs";
				}
				if($filter == "mostviewed")
				{
					$title = "Most Viewed Jobs";
				}
			}
			else {
				$this->db->where('id',$filter);
				$query = $this->db->get('category');
				$row = $query->row();
				$name = $row->category_name;
				
				$title = "$name";
				
			}		
			return $title;
		}
		function get_person_detail($id)
		{
			//Get the person id from the gig table
				$this->db->where('id',$id);
				$query = $this->db->get('gigs');
				$row = $query->row();
				$person_id = $row->member_posted_by;
				
				//Now check for this person on the members table
				$this->db->where('id',$person_id);
				$query = $this->db->get('members');
				$row1 = $query->row();
				$data['id'] = $row1->id;
				$data['name'] = $row1->member_name;
				$data['picture'] = $row1->member_picture;
				$data['description'] = $row1->member_description;
				$data['email'] = $row1->member_email;
				
				return $data;
		}
		function get_person_info($id)
		{
			
				//Now check for this person on the members table
				$this->db->where('id',$id);
				$query = $this->db->get('members');
				$row1 = $query->row();
				$data['id'] = $id;
				$data['name'] = $row1->member_name;
				$data['picture'] = $row1->member_picture;
				$data['description'] = $row1->member_description;
				$data['email'] = $row1->member_email;
				
				return $data;
		}
		function get_category_dropdown()
		{
			$categories = "";
			//Get a list of all active category
			$this->db->where('category_active','y');
			$query = $this->db->get('category');
			foreach($query->result() as $row)
			{
				$categories .= "<option value='$row->id'>$row->category_name</option>";
			}
			return $categories;
		
		}
		
		function filter_count($filter)
		{
				if(is_numeric($filter)) {
						$this->db->where('category',$filter);
						$query = $this->db->get('gigs');
						$count = $query->num_rows();					
				}
				else {
					$query = $this->db->get('gigs');
					$count = $query->num_rows();				
				
				}
				return $count;
		}
		function search($searchstring,$num,$offset)
		{
				$list = "";
			
				$this->db->like('title', $searchstring);	
				$this->db->where('approved','y');
				$this->db->where('member_disabled','n');
				$query = $this->db->get('gigs',$num,$offset);
				$count = $this->search_count($searchstring);
				$list .= "<div id='filter_title'><b>Your Searched for:</b> <span>".$searchstring."</span> <b>Total Results</b>: <span>$count</span></div>";
					foreach($query->result() as $row)
					{
							$list .= '<div id=single_listing>
								<div id=single_listing_image><img src=\''.base_url().'/assets/timthumb.php?src='.base_url().'/images/gigs/'.$row->image.'&h=70&w=100&zc=1\' alt=""><br/>
										</div>
								<div id=single_listing_content><a href='.site_url('service/single/'.$row->id).' class=listing-title>'.$row->title.' </a>'.
								$row->description.'</div>

									<div id=order><a href='.site_url('service/single/'.$row->id).' class="small yellow awesome">Read more</a></div>
									<div id=meta>Posted by: <a href="'.site_url('user/view/'.$this->Common_model->getusername($row->member_posted_by)).'">'.$this->Common_model->convertname($row->member_posted_by).'</a> on '.$this->Common_model->convert_date($row->date_added).'</div>
										<div id=share>Comment | Favorite | Save</div>
									</div>';
					}
				if($query->num_rows == '0')
				{
					$list .= "<div id=single_listing>No Services found matching your searched for.<br/><br/>Please try a different search term.</div>";
				}
				return $list;
		}
			function search_count($searchstring)
			{
						$this->db->like('title', $searchstring);	
						$this->db->where('approved','y');
						$query = $this->db->get('gigs');
							$count = $query->num_rows();		
						
					return $count;
			}
			function delete_detail($id)
			
			{
				$delete = "";
				//Get the title and the posted date
				$this->db->where('id',$id);
				$query = $this->db->get('gigs');
				foreach($query->result() as $row)
				{
					$delete .= "<b style='color:#FF6600'>".$row->title."<br/><br/></b>";
					$delete .= "<p><a href='".site_url('service/deleteconfirm/'.$id)."'>Yes, Delete</a> or <a href='".site_url('member/selling')."'>Go back</a>";
				}
				return $delete;
			}
			
			function edit_details($id)
			{
					$data['error'] = "";
					$this->db->where('id', $id);
					$query = $this->db->get('gigs');
					$row = $query->row();
					$data['id'] = $row->id;
					$data['title'] = $row->title;
					$data['category'] = $this->category_dropdown_edit($row->category);
				
					$data['description'] = $row->description;
					$data['information_required'] = $row->information_required;
					$data['tags'] = $row->tags;
					$data['maxnumofdays'] = $row->maxnumofdays;
					$data['image'] = $row->image;	
					
					return $data;
			}
			
			function category_dropdown_edit($id)
			{
				$categories = "";
				
				//Highlight current category of all active category
				$this->db->where('id',$id);
				$this->db->where('category_active','y');
				$query = $this->db->get('category');
				$row = $query->row();
				$categories .= "<option value='$row->id'>$row->category_name</option>";
				$categories .= "<option value=''>--------------------</option>";
				
				//Get a list of all active category
				$this->db->where('category_active','y');
				$query = $this->db->get('category');
				foreach($query->result() as $row)
				{
					$categories .= "<option value='$row->id'>$row->category_name</option>";
				}
				return $categories;
			}
			
			function get_reviews($id)
			{
				$review = "";
				$this->db->where('for_gig',$id);
				$query = $this->db->get('reviews');
				if($query->num_rows() > 0)
				{
				foreach($query->result() as $row)
				{
					$from_email = $this->get_person_detail($row->from_user);
					$email = trim($from_email['email']); // "MyEmailAddress@example.com"
					$email = strtolower($email); // "myemailaddress@example.com"
					$image = md5($email);
					
					$review .= "<li class='box'>
					
					<span class='com_name'>".$from_email['name']."</span> <br />".$row->review."</li>";
					
				}
			}
			else {
				$review = "No reviews for this service as yet";
			}
				return $review;
			}
			
			function post_comment()
			{
				$forgig=$this->input->post('for_gig');
				$forgig=mysql_real_escape_string($forgig);			
				$comment=$this->input->post('comment');
				$comment=mysql_real_escape_string($comment);			
			
				$date = date("Y-m-d");
				//Insert this into the database
				$data = array('for_gig'=>$forgig, 'from_user'=>$this->session->userdata('id'),'review'=>$comment,'date_added'=>$date);
				$this->db->insert('reviews',$data);
			}
			
			function check_status($id)
			{
				//Check if the person placing the order is not the same as the person providing the service
					$this->db->where('id',$id);
					$query = $this->db->get('gigs');
					$row = $query->row();
					$user_id = $row->member_posted_by;
					
					if($user_id != $this->session->userdata('id'))
					{
						$ok = TRUE; // OK to proceed
					}
					else {
						$ok = FALSE; // Do not proceed
					}
				return $ok;
			}

			
}
