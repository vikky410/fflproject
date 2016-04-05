<?php

class Rosters_model extends MY_Model
{
    function get_team_roster_data($teamid)
    {
        $data = $this->db->select('roster.id, roster.player_id') #roster
                ->select('player.short_name') #player
                ->select('nfl_team.club_id') #nfl_team
                ->select('nfl_position.text_id as position') #nfl_position
                ->from('roster')
                ->join('player', 'player.id = roster.player_id')
                ->join('nfl_team', 'nfl_team.id = player.nfl_team_id')
                ->join('nfl_position', 'nfl_position.id = player.nfl_position_id')
                ->where('roster.league_id', $this->leagueid)
                ->where('roster.team_id', $teamid)
                ->get();
        return $data->result();

    }
    
    function get_team_name($teamid)
    {
        $data = $this->db->select('team_name')
                ->from('team')
                ->where('team.id', $teamid)
                ->get();
        return $data->row()->team_name;
    }
    
    function player_is_available($playerid)
    {
        $data = $this->db->select('roster.id')
                ->from('roster')
                ->where('player_id', $playerid)
                ->where('league_id', $this->leagueid)
                ->get();
        if ($data->num_rows == 0)
          return true;
        return false;
    }
    
    function add_player_to_team($playerid, $teamid)
    {
        $data = array('league_id' => $this->leagueid,
            'team_id' => $teamid,
            'player_id' => $playerid);
        $this->db->insert('roster',$data);
    }
    
    function remove_player_from_team($playerid, $teamid)
    {
        $this->db->where('player_id', $playerid)
                ->where('team_id', $teamid)
                ->delete('roster');
    }
    
    
}