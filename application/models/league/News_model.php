<?php

class News_model extends MY_Model{

    function __construct(){
        parent::__construct();
        $this->teamid = $this->session->userdata('team_id');
        $this->current_year = $this->session->userdata('current_year');
        $this->current_week = $this->session->userdata('current_week');
        $this->leagueid = $this->session->userdata('league_id');
    }


    function get_news_data()
    {
    	return $this->db->select('id,data,title,date_posted,last_updated')->from('content')
    		->where('league_id',$this->leagueid)->where('text_id',"news")->order_by('date_posted','desc')
    		->get()->result();
    }

}

?>