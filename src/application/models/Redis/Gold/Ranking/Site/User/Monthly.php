<?php
class Redis_Gold_Ranking_Site_User_MonthlyModel extends Redis_Gold_Ranking_Site_User_BaseModel
{
    protected $pattern = 'Ym';

    protected $ttl = 2678400;
}