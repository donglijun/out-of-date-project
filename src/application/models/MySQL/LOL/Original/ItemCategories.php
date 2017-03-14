<?php
class MySQL_LOL_Original_ItemCategoriesModel extends MySQL_BaseIDModel
{
    protected $table = 'itemCategories';

    protected $fields = array(
        'id',
        'name',
    );

    protected $defaultFields = array(
        'id',
        'name',
    );
}