<?php
class Redis_Gold_Ranking_Site_User_DailyModel extends Redis_Gold_Ranking_Site_User_BaseModel
{
    protected $pattern = 'Ymd';

    protected $ttl = 86400;
}