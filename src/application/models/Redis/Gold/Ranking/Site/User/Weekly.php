<?php
class Redis_Gold_Ranking_Site_User_WeeklyModel extends Redis_Gold_Ranking_Site_User_BaseModel
{
    protected $pattern = 'oW';

    protected $ttl = 604800;
}