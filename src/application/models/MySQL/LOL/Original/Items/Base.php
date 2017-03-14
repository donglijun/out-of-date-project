<?php
class MySQL_LOL_Original_Items_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'items';

    protected $fields = array(
        'id',
        'name',
        'description',
        'iconPath',
        'price',
        'flatHPPoolMod',
        'flatMPPoolMod',
        'percentHPPoolMod',
        'percentMPPoolMod',
        'flatHPRegenMod',
        'percentHPRegenMod',
        'flatMPRegenMod',
        'percentMPRegenMod',
        'flatArmorMod',
        'percentArmorMod',
        'flatAttackDamageMod',
        'percentAttackDamageMod',
        'flatAbilityPowerMod',
        'percentAbilityPowerMod',
        'flatMovementSpeedMod',
        'percentMovementSpeedMod',
        'flatAttackSpeedMod',
        'percentAttackSpeedMod',
        'flatDodgeMod',
        'percentDodgeMod',
        'flatCritChanceMod',
        'percentCritChanceMod',
        'flatCritDamageMod',
        'percentCritDamageMod',
        'flatMagicResistMod',
        'percentMagicResistMod',
        'flatEXPBonus',
        'percentEXPBonus',
        'epicness',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'description',
        'iconPath',
        'price',
        'flatHPPoolMod',
        'flatMPPoolMod',
        'percentHPPoolMod',
        'percentMPPoolMod',
        'flatHPRegenMod',
        'percentHPRegenMod',
        'flatMPRegenMod',
        'percentMPRegenMod',
        'flatArmorMod',
        'percentArmorMod',
        'flatAttackDamageMod',
        'percentAttackDamageMod',
        'flatAbilityPowerMod',
        'percentAbilityPowerMod',
        'flatMovementSpeedMod',
        'percentMovementSpeedMod',
        'flatAttackSpeedMod',
        'percentAttackSpeedMod',
        'flatDodgeMod',
        'percentDodgeMod',
        'flatCritChanceMod',
        'percentCritChanceMod',
        'flatCritDamageMod',
        'percentCritDamageMod',
        'flatMagicResistMod',
        'percentMagicResistMod',
        'flatEXPBonus',
        'percentEXPBonus',
        'epicness',
    );

    public function formatForJS()
    {
        $result = array();

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = array(
                'name'          => $row['name'],
                'price'         => $row['price'],
                'description'   => $row['description'],
            );
        }

        return $result;
    }
}