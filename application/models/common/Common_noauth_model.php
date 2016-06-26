<?php
class Common_noauth_model extends CI_Model{

    function __construct(){
        parent::__construct();
    }

    function league_name_from_mask_id($maskid)
    {
        return $this->db->select('league_name')->from('league')->where('mask_id',$maskid)->get()->row()->league_name;
    }

    function get_site_name()
    {
        return $this->db->select('name')->from('site_settings')->get()->row()->name;
    }

    function join_code_required($maskid)
    {
        $row = $this->db->select('join_password')->from('league')
            ->join('league_settings','league_settings.league_id = league.id')
            ->where('league.mask_id',$maskid)->get()->row();

        if (count($row) > 0 && $row->join_password != "")
            return true;
        return false;
    }

    function valid_mask($maskid)
    {
        if ($this->db->from('league')->where('mask_id',$maskid)->count_all_results() > 0)
            return True;
        return False;
    }

    function league_has_room($maskid)
    {
        $row = $this->db->select('league_settings.max_teams, league_settings.league_id')->from('league_settings')
            ->join('league','league.id = league_settings.league_id')->where('mask_id',$maskid)->get()->row();
        $active_teams = $this->db->from('team')->where('league_id',$row->league_id)->where('active',1)->count_all_results();
        // If max teams is zero and active teams is zero, must be a new league, need to let someone join.

        if ($row->max_teams == 0 && $active_teams == 0)
        {
            return True;
        }
        if ($active_teams < $row->max_teams)
            return TRUE;
        return FALSE;

    }

    function set_session_variables($expire=False)
    {
        if ($this->session->userdata('expire_basic_vars') < time() || $expire)
        {
            // Need some variables from consolidated config file.
            $this->session->set_userdata('site_name',$this->get_site_name());
            $this->session->set_userdata('expire_basic_vars', time()+$this->session->userdata('session_refresh_time'));
            $this->config->load('fflproject');
            $this->session->set_userdata('basic_debug',$this->config->item('basic_debug'));
        }
    }

    function get_week_type($leagueid)
    {
        return $this->db->select('nfl_season')->from('league_settings')->where('league_id',$leagueid)->get()->row()->nfl_season;
    }

    function get_current_week($leagueid)
    {
        $row = $this->db->select('season_year')->from('league')->where('id',$leagueid)->get()->row();
        if (count($row) > 0)
            $season_year = $row->season_year;
        // Get the most recently past game start time.
        // If it's start time is more than 12 hours ago
        // Then get the next game is the current week.
        $current_time = time();
        $this->db->select('eid, week, year, UNIX_TIMESTAMP(start_time) as start')->from('nfl_schedule')
            ->where('start_time <',t_mysql($current_time));
        if (isset($season_year))
            $this->db->where('year',$season_year);
        $most_recent=$this->db->order_by('start_time','desc')->limit(1)->get()->row();

        $this->db->select('eid, week, year, UNIX_TIMESTAMP(start_time) as start')->from('nfl_schedule')
            ->where('start_time >',t_mysql($current_time));
        if(isset($season_year))
            $this->db->where('year',$season_year);
        $next_game = $this->db->order_by('start_time','asc')->limit(1)->get()->row();

        if (count($next_game) == 0)
            return $most_recent;

        // It's mid week, works for Thursday through Sunday
        if ($most_recent->week == $next_game->week)
            return $next_game;
        else  // It's after Monday night, need to adjust to allow MNF to end.
        {
            // If the most recent game is 12 hours in the past, roll to the next week.
            if ($most_recent->start + (60*60*12) < $current_time)
                return $next_game;
            else
                return $most_recent;
        }
    }

    function league_position_year($leagueid, $year = 0)
    {
        if ($year == 0) // If none passed, assume they want to know for the current year
        {
            $week_year = $this->get_current_week($leagueid);
            $year = $week_year->year;
        }
        $pos_year = $this->db->select('max(year) as y')->from('position')->where('position.league_id',$leagueid)
                ->where('year <=',$year)->get()->row()->y;
        if($pos_year != "")
            return $pos_year;
        return 0;
    }

    function player_game_start_time($playerid, $year, $week, $weektype)
    {
        $club_id = $this->player_club_id($playerid);
        $row = $this->db->select('UNIX_TIMESTAMP(start_time) as start_time')->from('nfl_schedule')
            ->where('year = '.$year.' and week ='.$week.' and gt ="'.$weektype.'"'.
            ' and (h="'.$club_id.'" or v="'.$club_id.'")')
            ->get()->row();

        if (count($row) == 0)
            return "";
        else
            return $row->start_time;
    }

    function player_club_id($playerid)
    {
        return $this->db->select('nfl_team.club_id')->from('player')->join('nfl_team','nfl_team.id = player.nfl_team_id')
            ->where('player.id',$playerid)->get()->row()->club_id;
    }

    function get_leagues_data()
    {
        return $this->db->select('id')->from('league')->get()->result();
    }
}
?>
