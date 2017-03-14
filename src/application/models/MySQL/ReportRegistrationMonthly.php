<?php
class MySQL_ReportRegistrationMonthlyModel extends MySQL_ReportBaseModel
{
    protected $table = 'report_registration_monthly';

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