<?php
class Redis_Gold_Ranking_Site_Channel_MonthlyModel extends Redis_Gold_Ranking_Site_Channel_BaseModel
{
    protected $pattern = 'Ym';

    protected $ttl = 2678400;
}