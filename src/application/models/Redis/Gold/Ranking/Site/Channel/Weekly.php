<?php
class Redis_Gold_Ranking_Site_Channel_WeeklyModel extends Redis_Gold_Ranking_Site_Channel_BaseModel
{
    protected $pattern = 'oW';

    protected $ttl = 604800;
}