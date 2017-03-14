<?php
class Redis_Gift_Ranking_WeeklyModel extends Redis_Gift_Ranking_BaseModel
{
    protected $pattern = 'oW';

    protected $ttl = 604800;
}