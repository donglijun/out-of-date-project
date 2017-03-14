<?php
class Redis_Streaming_Broadcast_Highlight_Ranking_MonthlyModel extends Redis_Streaming_Broadcast_Highlight_Ranking_BaseModel
{
    protected $pattern = 'Ym';

    protected $ttl = 2678400;
}