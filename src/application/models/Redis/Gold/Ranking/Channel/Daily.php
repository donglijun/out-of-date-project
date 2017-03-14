<?php
class Redis_Gold_Ranking_Channel_DailyModel extends Redis_Gold_Ranking_Channel_BaseModel
{
    protected $pattern = 'Ymd';

    protected $ttl = 86400;
}