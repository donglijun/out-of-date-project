<?php
class MySQL_Voice_RoomModel extends MySQL_BaseIDModel
{
    protected $table = 'room';

    protected $fields = array(
        'id',
        'title',
        'icon',
        'creator',
        'created_on',
        'owner',
        'password',
        'is_online',
        'current_online',
        'options',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'icon',
        'creator',
        'created_on',
        'owner',
        'password',
        'is_online',
        'current_online',
        'options',
    );

    protected $defaultOptions = array(
        'version'                   => 1,
        'max_speak_time'            => 300,
        'send_text_cooldown'        => 5,
        'max_send_text_length'      => 1000,
        'max_emoji_in_text'         => 5,
        'send_image_cooldown'       => 60,
        'valid_image_types'         => 'jpg,png,gif,bmp',
        'max_image_size'            => 1024,
        'mute_time_before_speak'    => 15,
        'announcement'              => '',
    );

    public function isOwner($room, $user)
    {
        $sql = "SELECT `owner` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $room,
        ));

        return $stmt->fetchColumn() == $user;
    }

//    public function addAdmin($room, $user)
//    {
//        $result = false;
//
//        $sql = "SELECT `admins` FROM `{$this->schema}`.`{$this->table}` WHERE `room`=:room";
//        $stmt = $this->db->prepare($sql);
//        $stmt->execute(array(
//            ':room' => $room,
//        ));
//
//        $admins = $stmt->fetchColumn();
//
//        if ($admins !== false) {
//            $admins = $admins ? json_decode($admins, true) : array();
//            $admins[$user] = time();
//            $admins = json_encode($admins);
//
//            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `admins`=:admins WHERE `room`=:room";
//            $stmt = $this->db->prepare($sql);
//            $result = $stmt->execute(array(
//                ':admins'   => $admins,
//                ':room'     => $room,
//            ));
//        }
//
//        return $result;
//    }
//
//    public function removeAdmin($room, $user)
//    {
//        $result = false;
//
//        $sql = "SELECT `admins` FROM `{$this->schema}`.`{$this->table}` WHERE `room`=:room";
//        $stmt = $this->db->prepare($sql);
//        $stmt->execute(array(
//            ':room' => $room,
//        ));
//
//        $admins = $stmt->fetchColumn();
//
//        if ($admins !== false) {
//            $admins = $admins ? json_decode($admins, true) : array();
//            if (isset($admins[$user])) {
//                unset($admins[$user]);
//                $admins = json_encode($admins);
//
//                $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `admins`=:admins WHERE `room`=:room";
//                $stmt = $this->db->prepare($sql);
//                $result = $stmt->execute(array(
//                    ':admins'   => $admins,
//                    ':room'     => $room,
//                ));
//            }
//        }
//
//        return $result;
//    }

    public function authenticate($room, $password)
    {
        $sql = "SELECT `password` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $room,
        ));

        $passwordHash = $stmt->fetchColumn();

        return $password && password_verify($password, $passwordHash);
    }

    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    public function updateOptions($room, $options)
    {
        $result = false;
        $data = array();

        if ($options) {
            foreach ($options as $key => $val) {
                if (isset($this->defaultOptions[$key])) {
                    $data[$key] = $val;
                }
            }

            if (($options = $this->getOptions($room)) !== false) {
                $options = array_merge($options, $data);
                $options = json_encode($options);

                $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `options`=:options WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':options'  => $options,
                    ':id'       => $room,
                ));

                $result = true;
            }
        }

        return $result;
    }

    public function getOptions($room)
    {
        $result = false;

        $sql = "SELECT `options` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $room,
        ));

        if ($options = $stmt->fetchColumn()) {
            $result = json_decode($options, true);
        }

        return $result;
    }
}