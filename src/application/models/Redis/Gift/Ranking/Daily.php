<?php
class Redis_Gift_Ranking_DailyModel extends Redis_Gift_Ranking_BaseModel
{
    protected $pattern = 'Ymd';

    protected $ttl = 86400;
}