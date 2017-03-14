<?php
class MySQL_LOL_Original_ItemItemCategories_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'itemItemCategories';

    protected $fields = array(
        'id',
        'itemId',
        'itemCategoryId'
    );

    protected $defaultFields = array(
        'id',
        'itemId',
        'itemCategoryId'
    );
}