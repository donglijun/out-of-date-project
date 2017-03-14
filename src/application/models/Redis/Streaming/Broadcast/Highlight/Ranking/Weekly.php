<?php
class Redis_Streaming_Broadcast_Highlight_Ranking_WeeklyModel extends Redis_Streaming_Broadcast_Highlight_Ranking_BaseModel
{
    protected $pattern = 'oW';

    protected $ttl = 604800;
}