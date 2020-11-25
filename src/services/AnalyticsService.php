<?php

namespace doublesecretagency\viewcount\services;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQuery;

class AnalyticsService extends Component
{

    public function buildNodeAnalyticsTable()
    {
        // $rows = (new craft\db\Query())
        //     ->select(['users.firstName', 
        //     'users.lastName', 'content.field_jobTitle',
        //     'content.field_userCompanyName', 'users.email', 'content.field_city',
        //     'content.field_country', 'content.title', 'entrytypes.name'])
        //     ->from('content')
        //     ->innerJoin('{{%viewcount_viewlog}}', '[[viewcount_viewlog.elementId]] = [[content.elementId]]')
        //     ->innerJoin('elements', '[[content.elementId]] = [[elements.id]]')
        //     ->innerJoin('entrytypes', '[[elements.fieldLayoutId]] = [[entrytypes.fieldLayoutId]]')
        //     ->innerJoin('entries', '[[elements.id]] = [[entries.id]]')
        //     ->innerJoin('users', '[[viewcount_viewlog.userId]] = [[users.id]]')
        //     ->where(['users.firstName' => 'Savvy', 'users.lastName' => 'Investor'])
        //     ->all();

        Craft::$app->db->createCommand()->insert('viewcount_s2nodeanalytics', [
            'nid' => 1,
            'title' => 'Test',
            'uid' => 232,
            'click' => 0,
            'dateCreated' => date("Y-m-d H:i:s"),
            'dateUpdated' => date("Y-m-d H:i:s"),
        ])->execute();

        // var_dump($rows); die();
        // return $rows;
    }

}