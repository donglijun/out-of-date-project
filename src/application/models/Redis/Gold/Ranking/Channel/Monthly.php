<?php
class Redis_Gold_Ranking_Channel_MonthlyModel extends Redis_Gold_Ranking_Channel_BaseModel
{
    protected $pattern = 'Ym';

    protected $ttl = 2678400;
}