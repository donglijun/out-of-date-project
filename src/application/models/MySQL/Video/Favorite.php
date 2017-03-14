<?php
class MySQL_Video_FavoriteModel extends MySQL_BaseIDModel
{
    protected $table = 'favorite';

    protected $fields = array(
        'id',
        'user',
        'link',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'link',
        'created_on',
    );

    public function add($user, $link)
    {
        $data = array(
            'user'          => $user,
            'link'          => $link,
            'created_on'    => time(),
        );

        return $this->replace($data);
    }

    public function remove($user, $link)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user AND `link`=:link";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':user' => $user,
            ':link' => $link,
        ));
    }
}