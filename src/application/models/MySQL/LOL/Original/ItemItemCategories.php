<?php
class MySQL_LOL_Original_ItemItemCategoriesModel extends MySQL_BaseIDModel
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