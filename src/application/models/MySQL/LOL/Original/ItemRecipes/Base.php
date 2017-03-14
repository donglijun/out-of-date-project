<?php
class MySQL_LOL_Original_ItemRecipes_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'itemRecipes';

    protected $fields = array(
        'id',
        'recipeItemId',
        'buildsToItemId',
    );

    protected $defaultFields = array(
        'id',
        'recipeItemId',
        'buildsToItemId',
    );
}