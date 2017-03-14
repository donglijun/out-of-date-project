<?php
class MySQL_ReportRegistrationWeeklyModel extends MySQL_ReportBaseModel
{
    protected $table = 'report_registration_weekly';

    protected $fields = array(
        'date',
        'increment',
        'growth_rate',
        'updated_on',
    );

    protected $defaultFields = array(
        'date',
        'increment',
        'growth_rate',
        'updated_on',
    );
}