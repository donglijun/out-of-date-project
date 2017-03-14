<?php
class Redis_Streaming_Broadcast_Highlight_Ranking_DailyModel extends Redis_Streaming_Broadcast_Highlight_Ranking_BaseModel
{
    protected $pattern = 'Ymd';

    protected $ttl = 86400;
}