<?php
class Redis_Gift_Ranking_MonthlyModel extends Redis_Gift_Ranking_BaseModel
{
    protected $pattern = 'Ym';

    protected $ttl = 2678400;
}